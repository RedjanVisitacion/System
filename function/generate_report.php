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

        $stmt = $con->prepare("SELECT c.candidate_id, c.name, c.department, c.position, c.age, c.platform, COALESCE(r.votes, 0) as votes, r.published_at FROM candidate c LEFT JOIN result r ON c.candidate_id = r.candidate_id ORDER BY c.department, c.position, r.votes DESC");
        if (!$stmt) throw new Exception("Failed to prepare candidates query: " . $con->error);
        if (!$stmt->execute()) throw new Exception("Failed to execute candidates query: " . $stmt->error);
        $candidates = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        $stmt = $con->prepare("SELECT department, COUNT(DISTINCT user_id) as total_voters FROM user WHERE role = 'student' GROUP BY department");
        if (!$stmt) throw new Exception("Failed to prepare department stats query: " . $con->error);
        if (!$stmt->execute()) throw new Exception("Failed to execute department stats query: " . $stmt->error);
        $departmentStats = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        $voteQuery = "SELECT SUM(COALESCE(votes, 0)) as total_votes FROM result";
        if ($startDate && $endDate) $voteQuery .= " WHERE published_at BETWEEN ? AND ?";
        $stmt = $con->prepare($voteQuery);
        if (!$stmt) throw new Exception("Failed to prepare total votes query: " . $con->error);
        if ($startDate && $endDate) $stmt->bind_param("ss", $startDate, $endDate);
        if (!$stmt->execute()) throw new Exception("Failed to execute total votes query: " . $stmt->error);
        $row = $stmt->get_result()->fetch_assoc();
        $totalVotes = intval($row['total_votes']);
        $stmt->close();

        $reportData = [
            'report_period' => [ 'start_date' => $startDate, 'end_date' => $endDate ],
            'election_periods' => $electionDates,
            'department_statistics' => $departmentStats,
            'candidate_results' => $candidates,
            'total_votes_cast' => $totalVotes,
            'generated_at' => date('Y-m-d H:i:s')
        ];

        $reportsDir = '../reports';
        if (!file_exists($reportsDir)) mkdir($reportsDir, 0777, true);

        $timestamp = date('Y-m-d_H-i-s');
        $dateRange = $startDate && $endDate ? '_' . date('Y-m-d', strtotime($startDate)) . '_to_' . date('Y-m-d', strtotime($endDate)) : '';
        $filename = "election_report{$dateRange}_{$timestamp}.{$format}";
        $filepath = "$reportsDir/$filename";

        if ($format === 'txt') {
            $textReport = generateTextReport($reportData);
            if (file_put_contents($filepath, $textReport) === false) throw new Exception("Failed to write text report file");
            header('Content-Type: text/plain');
            header('Content-Disposition: inline; filename="' . basename($filepath) . '"');
            header('Content-Length: ' . filesize($filepath));
            readfile($filepath);
            unlink($filepath);
        } else {
            throw new Exception("Invalid format specified");
        }

        return [ 'success' => true, 'message' => 'Report generated and opened for printing', 'total_votes_cast' => $totalVotes ];

    } catch (Exception $e) {
        error_log("Report generation error: " . $e->getMessage());
        return [ 'success' => false, 'message' => 'Error generating report: ' . $e->getMessage() ];
    }
}

function generateTextReport($data) {
    global $con;
    $report = "ELECTION REPORT\n==============\n\n";
    if ($data['report_period']['start_date'] && $data['report_period']['end_date']) {
        $report .= "Report Period:\nFrom: " . date('F j, Y', strtotime($data['report_period']['start_date'])) . "\nTo: " . date('F j, Y', strtotime($data['report_period']['end_date'])) . "\n\n";
    }

    $report .= "Election Periods:\n================\n";
    foreach ($data['election_periods'] as $period) {
        $report .= "Start Date: " . date('F j, Y g:i A', strtotime($period['start_date'])) . "\n";
        $report .= "End Date: " . date('F j, Y g:i A', strtotime($period['end_date'])) . "\n";
        $report .= "Results Date: " . date('F j, Y g:i A', strtotime($period['results_date'])) . "\n\n";
    }

    $report .= "Department Statistics:\n====================\n";
    foreach ($data['department_statistics'] as $dept) {
        $report .= "{$dept['department']}: {$dept['total_voters']} registered voters\n";
    }
    $report .= "\n";

    $totalVotesQuery = "SELECT COUNT(DISTINCT user_id) as total_votes FROM vote";
    $totalVotesResult = $con->query($totalVotesQuery);
    $totalVotes = $totalVotesResult->fetch_assoc()['total_votes'];

    $report .= "Total Votes Cast: {$totalVotes}\n\n";
    $report .= "List of Voters:\n";
    $votedUsersQuery = "SELECT DISTINCT u.user_id, up.full_name, up.program_name FROM vote v JOIN user u ON v.user_id = u.user_id JOIN user_profile up ON u.user_id = up.user_id ORDER BY up.full_name ASC";
    $votedUsersResult = $con->query($votedUsersQuery);
    while ($row = $votedUsersResult->fetch_assoc()) {
        $report .= "- {$row['full_name']} ({$row['program_name']})\n";
    }
    $report .= "\nCandidate Results:\n=================\n";
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
    $report .= "\nReport generated at: " . date('F j, Y g:i A', strtotime($data['generated_at'])) . "\n";
    return $report;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $format = $_POST['format'] ?? 'txt';
    $startDate = $_POST['start_date'] ?? null;
    $endDate = $_POST['end_date'] ?? null;

    if ($format !== 'txt') {
        echo json_encode(['success' => false, 'message' => 'Only TXT format is supported']);
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