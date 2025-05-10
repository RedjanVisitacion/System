<?php
require_once '../src/connection.php';
require_once '../src/check_session.php';

$vendorAutoload = '../vendor/autoload.php';
if (file_exists($vendorAutoload)) {
    require_once $vendorAutoload;
}

function generateElectionReport($format = 'txt', $startDate = null, $endDate = null) {
    global $con;

    try {
        // Fetch election periods
        $dateQuery = "SELECT * FROM election_dates";
        $params = [];
        $types = "";

        if ($startDate && $endDate) {
            $dateQuery .= " WHERE (start_date BETWEEN ? AND ?) OR (end_date BETWEEN ? AND ?) OR (? BETWEEN start_date AND end_date)";
            $params = [$startDate, $endDate, $startDate, $endDate, $startDate];
            $types = "sssss";
        }

        $dateQuery .= " ORDER BY id DESC";
        $stmt = $con->prepare($dateQuery);
        if (!$stmt) throw new Exception("Failed to prepare election dates query: " . $con->error);
        if (!empty($params)) $stmt->bind_param($types, ...$params);
        if (!$stmt->execute()) throw new Exception("Failed to execute election dates query: " . $stmt->error);
        $electionDates = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        if (empty($electionDates)) return ['success' => false, 'message' => 'No election data found for the selected date range'];
        $stmt->close();

        // Fetch candidates and votes
        $stmt = $con->prepare("
            SELECT c.candidate_id, c.name, c.department, c.position, c.age, c.platform,
                   COALESCE(r.votes, 0) AS votes, r.published_at
            FROM candidate c
            LEFT JOIN result r ON c.candidate_id = r.candidate_id
            ORDER BY c.department, c.position, r.votes DESC
        ");
        if (!$stmt) throw new Exception("Failed to prepare candidates query: " . $con->error);
        if (!$stmt->execute()) throw new Exception("Failed to execute candidates query: " . $stmt->error);
        $candidates = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        // Fetch department stats
        $stmt = $con->prepare("
            SELECT u.department, COUNT(DISTINCT u.user_id) AS total_voters
            FROM user u
            JOIN user_profile up ON u.user_id = up.user_id
            WHERE u.role = 'student'
            GROUP BY u.department
        ");
        if (!$stmt) throw new Exception("Failed to prepare department stats query: " . $con->error);
        if (!$stmt->execute()) throw new Exception("Failed to execute department stats query: " . $stmt->error);
        $departmentStats = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        // Get total registered voters
        $totalVotersQuery = "
            SELECT COUNT(*) AS total_registered
            FROM user u
            JOIN user_profile up ON u.user_id = up.user_id
            WHERE u.role = 'student'
        ";
        $stmt = $con->prepare($totalVotersQuery);
        if (!$stmt) throw new Exception("Failed to prepare total voters query: " . $con->error);
        if (!$stmt->execute()) throw new Exception("Failed to execute total voters query: " . $stmt->error);
        $totalRegisteredVoters = $stmt->get_result()->fetch_assoc()['total_registered'];
        $stmt->close();

        // Fetch total votes cast
        $totalVotesQuery = "SELECT COUNT(DISTINCT user_id) as total_votes FROM vote";
        $totalVotesResult = $con->query($totalVotesQuery);
        if (!$totalVotesResult) throw new Exception("Failed to execute total votes query: " . $con->error);
        $totalVotes = $totalVotesResult->fetch_assoc()['total_votes'];

        $reportData = [
            'report_period' => ['start_date' => $startDate, 'end_date' => $endDate],
            'election_periods' => $electionDates,
            'department_statistics' => $departmentStats,
            'candidate_results' => $candidates,
            'total_votes_cast' => $totalVotes,
            'total_registered_voters' => $totalRegisteredVoters,
            'generated_at' => date('Y-m-d H:i:s')
        ];

        $reportsDir = '../reports';
        if (!file_exists($reportsDir)) mkdir($reportsDir, 0777, true);

        $timestamp = date('Y-m-d_H-i-s');
        $dateRange = ($startDate && $endDate) ? '_' . date('Y-m-d', strtotime($startDate)) . '_to_' . date('Y-m-d', strtotime($endDate)) : '';
        $filename = "election_report{$dateRange}_{$timestamp}.{$format}";
        $filepath = "$reportsDir/$filename";

        if ($format === 'txt') {
            $textReport = generateTextReport($reportData, $format);
            if (file_put_contents($filepath, $textReport) === false) throw new Exception("Failed to write text report file");

            // Output file content directly
            header('Content-Type: text/plain');
            header('Content-Disposition: attachment; filename="' . basename($filepath) . '"');
            header('Content-Length: ' . filesize($filepath));
            readfile($filepath);
            unlink($filepath);
        } else if ($format === 'pdf') {
            // Redirect to PDF generation
            require_once 'generate_pdf_report.php';
            $result = generatePDFReport($format, $startDate, $endDate);
            if (!$result['success']) {
                throw new Exception($result['message']);
            }
            return $result;
        } else {
            throw new Exception("Invalid format specified");
        }

        return ['success' => true, 'message' => 'Report generated successfully', 'total_votes_cast' => $totalVotes];

    } catch (Exception $e) {
        error_log("Report generation error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Error generating report: ' . $e->getMessage()];
    }
}

function generateTextReport($data, $format) {
    global $con;

    $report = "ELECTION REPORT\n================\n\n";

    if ($data['report_period']['start_date'] && $data['report_period']['end_date']) {
        $report .= "Report Period:\nFrom: " . date('F j, Y', strtotime($data['report_period']['start_date'])) . "\nTo: " . date('F j, Y', strtotime($data['report_period']['end_date'])) . "\n\n";
    }

    $report .= "Election Periods:\n-----------------\n";
    foreach ($data['election_periods'] as $period) {
        $report .= "Start: " . date('F j, Y g:i A', strtotime($period['start_date'])) . "\n";
        $report .= "End: " . date('F j, Y g:i A', strtotime($period['end_date'])) . "\n";
        $report .= "Results Date: " . date('F j, Y g:i A', strtotime($period['results_date'])) . "\n\n";
    }

    $report .= "Voter Statistics:\n-----------------\n";
    $report .= "Total Registered Voters: {$data['total_registered_voters']}\n";
    $report .= "Total Votes Cast: {$data['total_votes_cast']}\n";
    $turnout = $data['total_registered_voters'] > 0 ? 
        round(($data['total_votes_cast'] / $data['total_registered_voters']) * 100, 2) : 0;
    $report .= "Voter Turnout: {$turnout}%\n\n";

    $report .= "Department Statistics:\n-----------------------\n";
    foreach ($data['department_statistics'] as $dept) {
        $report .= "{$dept['department']}: {$dept['total_voters']} registered voters\n";
    }

    // Voters list and count
    $report .= "\nTotal Votes Cast: {$data['total_votes_cast']}\n\n";
    $report .= "List of Voters:\n---------------\n";

    $votedUsersQuery = "
        SELECT DISTINCT u.user_id, up.full_name, up.program_name
        FROM vote v
        JOIN user u ON v.user_id = u.user_id
        JOIN user_profile up ON u.user_id = up.user_id
        ORDER BY up.full_name ASC
    ";
    $votedUsersResult = $con->query($votedUsersQuery);
    while ($row = $votedUsersResult->fetch_assoc()) {
        $report .= "- {$row['full_name']} ({$row['program_name']})\n";
    }

    // Candidate results
    $report .= "\nCandidate Results:\n------------------\n";
    $currentDepartment = '';
    $currentPosition = '';

    foreach ($data['candidate_results'] as $candidate) {
        if ($candidate['department'] !== $currentDepartment) {
            $currentDepartment = $candidate['department'];
            $report .= "\n{$currentDepartment}\n" . str_repeat('-', strlen($currentDepartment)) . "\n";
            $currentPosition = '';
        }
        if ($candidate['position'] !== $currentPosition) {
            $currentPosition = $candidate['position'];
            $report .= "\n{$currentPosition}:\n";
        }
        $report .= "{$candidate['name']} - {$candidate['votes']} votes\n";
    }

    // Set timezone to Asia/Manila and get current time
    date_default_timezone_set('Asia/Manila');
    $currentTime = new DateTime();
    $currentTime->modify('-3 hours');
    $report .= "\nReport generated at: " . $currentTime->format('F j, Y g:i A') . " (Philippine Time)\n";
    $report .= "Generated Format: " . strtoupper($format) . "\n";
    return $report;
}

function generatePDFReport($data) {
    $html = '<h1 style="text-align:center;color:#0d6efd;">ELECTION REPORT</h1>';
    $html .= '<hr style="border:1px solid #0d6efd;">';

    if ($data['report_period']['start_date'] && $data['report_period']['end_date']) {
        $html .= '<h3>Report Period:</h3>';
        $html .= '<p>From: ' . date('F j, Y', strtotime($data['report_period']['start_date'])) . '</p>';
        $html .= '<p>To: ' . date('F j, Y', strtotime($data['report_period']['end_date'])) . '</p><br>';
    }

    $html .= '<h3>Election Periods:</h3>';
    foreach ($data['election_periods'] as $period) {
        $html .= '<p>Start: ' . date('F j, Y g:i A', strtotime($period['start_date'])) . '</p>';
        $html .= '<p>End: ' . date('F j, Y g:i A', strtotime($period['end_date'])) . '</p>';
        $html .= '<p>Results Date: ' . date('F j, Y g:i A', strtotime($period['results_date'])) . '</p><br>';
    }

    $html .= '<h3>Voter Statistics:</h3>';
    $html .= '<p>Total Registered Voters: ' . $data['total_registered_voters'] . '</p>';
    $html .= '<p>Total Votes Cast: ' . $data['total_votes_cast'] . '</p>';
    $turnout = $data['total_registered_voters'] > 0 ? 
        round(($data['total_votes_cast'] / $data['total_registered_voters']) * 100, 2) : 0;
    $html .= '<p>Voter Turnout: ' . $turnout . '%</p><br>';

    $html .= '<h3>Department Statistics:</h3>';
    foreach ($data['department_statistics'] as $dept) {
        $html .= '<p>' . $dept['department'] . ': ' . $dept['total_voters'] . ' registered voters</p>';
    }
    $html .= '<br>';

    $html .= '<h3>Candidate Results:</h3>';
    $currentDepartment = '';
    $currentPosition = '';

    foreach ($data['candidate_results'] as $candidate) {
        if ($candidate['department'] !== $currentDepartment) {
            $currentDepartment = $candidate['department'];
            $html .= '<h4>' . $currentDepartment . '</h4>';
            $currentPosition = '';
        }
        if ($candidate['position'] !== $currentPosition) {
            $currentPosition = $candidate['position'];
            $html .= '<h5>' . $currentPosition . ':</h5>';
        }
        $html .= '<p>' . $candidate['name'] . ' - ' . $candidate['votes'] . ' votes</p>';
    }

    // Set timezone to Asia/Manila and get current time
    date_default_timezone_set('Asia/Manila');
    $currentTime = new DateTime();
    $currentTime->modify('-3 hours');
    $html .= '<hr style="border:1px solid #0d6efd;">';
    $html .= '<p style="text-align:center;">Report generated at: ' . $currentTime->format('F j, Y g:i A') . ' (Philippine Time)</p>';
    $html .= '<p style="text-align:center;">Generated Format: PDF</p>';

    return $html;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $format = $_POST['format'] ?? 'txt';
    $startDate = $_POST['start_date'] ?? null;
    $endDate = $_POST['end_date'] ?? null;

    if ($format !== 'txt' && $format !== 'pdf') {
        echo json_encode(['success' => false, 'message' => 'Only TXT or PDF formats are supported']);
        exit;
    }

    if ($startDate && $endDate) {
        if (!strtotime($startDate) || !strtotime($endDate)) {
            echo json_encode(['success' => false, 'message' => 'Invalid date format']);
            exit;
        }
        if (strtotime($startDate) > strtotime($endDate)) {
            echo json_encode(['success' => false, 'message' => 'Start date cannot be after end date']);
            exit;
        }
    }

    $result = generateElectionReport($format, $startDate, $endDate);
    echo json_encode($result);
    exit;
}
