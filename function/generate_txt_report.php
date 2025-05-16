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
        // Verify database connection
        if ($con->connect_error) {
            throw new Exception("Database connection failed: " . $con->connect_error);
        }

        // Fetch election periods
        $dateQuery = "SELECT * FROM election_dates";
        $params = [];
        $types = "";

        if ($startDate && $endDate) {
            // Modified query to be more inclusive of date ranges
            $dateQuery .= " WHERE 
                (start_date <= ? AND end_date >= ?) OR  -- Election period contains the entire date range
                (start_date BETWEEN ? AND ?) OR        -- Start date falls within the range
                (end_date BETWEEN ? AND ?) OR          -- End date falls within the range
                (? BETWEEN start_date AND end_date)    -- Any date in range falls within an election period
            ";
            $params = [
                $endDate, $startDate,    // For first condition
                $startDate, $endDate,    // For second condition
                $startDate, $endDate,    // For third condition
                $startDate               // For fourth condition
            ];
            $types = "sssssss";
        }

        $dateQuery .= " ORDER BY id DESC";
        
        // Debug information
        error_log("Date Query: " . $dateQuery);
        error_log("Parameters: " . print_r($params, true));
        
        $stmt = $con->prepare($dateQuery);
        if (!$stmt) {
            throw new Exception("Failed to prepare election dates query: " . $con->error);
        }
        
        if (!empty($params)) {
            if (!$stmt->bind_param($types, ...$params)) {
                throw new Exception("Failed to bind parameters: " . $stmt->error);
            }
        }
        
        if (!$stmt->execute()) {
            throw new Exception("Failed to execute election dates query: " . $stmt->error);
        }
        
        $result = $stmt->get_result();
        if (!$result) {
            throw new Exception("Failed to get result: " . $stmt->error);
        }
        
        $electionDates = $result->fetch_all(MYSQLI_ASSOC);
        
        // Debug information
        error_log("Number of election dates found: " . count($electionDates));
        error_log("Election dates: " . print_r($electionDates, true));
        
        if (empty($electionDates)) {
            // If no dates found, try getting all election dates without date range
            $fallbackQuery = "SELECT * FROM election_dates ORDER BY id DESC";
            $fallbackResult = $con->query($fallbackQuery);
            if ($fallbackResult) {
                $electionDates = $fallbackResult->fetch_all(MYSQLI_ASSOC);
                if (!empty($electionDates)) {
                    error_log("Using fallback query - found " . count($electionDates) . " election dates");
                } else {
                    throw new Exception('No election data found in the database');
                }
            } else {
                throw new Exception('No election data found for the selected date range and fallback query failed');
            }
        }
        $stmt->close();

        // Fetch candidates and votes with error handling
        $candidatesQuery = "
            SELECT c.candidate_id, c.name, c.department, c.position, c.age, c.platform,
                   COALESCE(r.votes, 0) AS votes, r.published_at
            FROM candidate c
            LEFT JOIN result r ON c.candidate_id = r.candidate_id
            ORDER BY c.department, c.position, r.votes DESC
        ";
        
        $stmt = $con->prepare($candidatesQuery);
        if (!$stmt) {
            throw new Exception("Failed to prepare candidates query: " . $con->error);
        }
        
        if (!$stmt->execute()) {
            throw new Exception("Failed to execute candidates query: " . $stmt->error);
        }
        
        $result = $stmt->get_result();
        if (!$result) {
            throw new Exception("Failed to get candidates result: " . $stmt->error);
        }
        
        $candidates = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        // Fetch department stats with error handling
        $deptStatsQuery = "
            SELECT u.department, COUNT(DISTINCT u.user_id) AS total_voters
            FROM user u
            JOIN user_profile up ON u.user_id = up.user_id
            WHERE u.role = 'student'
            GROUP BY u.department
        ";
        
        $stmt = $con->prepare($deptStatsQuery);
        if (!$stmt) {
            throw new Exception("Failed to prepare department stats query: " . $con->error);
        }
        
        if (!$stmt->execute()) {
            throw new Exception("Failed to execute department stats query: " . $stmt->error);
        }
        
        $result = $stmt->get_result();
        if (!$result) {
            throw new Exception("Failed to get department stats result: " . $stmt->error);
        }
        
        $departmentStats = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        // Get total registered voters with error handling
        $totalVotersQuery = "
            SELECT COUNT(*) AS total_registered
            FROM user u
            JOIN user_profile up ON u.user_id = up.user_id
            WHERE u.role = 'student'
        ";
        
        $stmt = $con->prepare($totalVotersQuery);
        if (!$stmt) {
            throw new Exception("Failed to prepare total voters query: " . $con->error);
        }
        
        if (!$stmt->execute()) {
            throw new Exception("Failed to execute total voters query: " . $stmt->error);
        }
        
        $result = $stmt->get_result();
        if (!$result) {
            throw new Exception("Failed to get total voters result: " . $stmt->error);
        }
        
        $totalRegisteredVoters = $result->fetch_assoc()['total_registered'];
        $stmt->close();

        // Fetch total votes cast with error handling
        $totalVotesQuery = "SELECT COUNT(DISTINCT user_id) as total_votes FROM vote";
        $result = $con->query($totalVotesQuery);
        if (!$result) {
            throw new Exception("Failed to execute total votes query: " . $con->error);
        }
        
        $totalVotes = $result->fetch_assoc()['total_votes'];

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
        if (!file_exists($reportsDir)) {
            mkdir($reportsDir, 0777, true);
        }

        $timestamp = date('Y-m-d_H-i-s');
        $dateRange = ($startDate && $endDate) ? '_' . date('Y-m-d', strtotime($startDate)) . '_to_' . date('Y-m-d', strtotime($endDate)) : '';
        $filename = "election_report{$dateRange}_{$timestamp}.{$format}";
        $filepath = "$reportsDir/$filename";

        if ($format === 'txt') {
            $textReport = generateTextReport($reportData, $format);
            if (file_put_contents($filepath, $textReport) === false) {
                throw new Exception("Failed to write text report file");
            }

            // Clear any output buffers
            while (ob_get_level()) {
                ob_end_clean();
            }

            // Set headers for text file download
            header('Content-Type: text/plain');
            header('Content-Disposition: attachment; filename="' . basename($filepath) . '"');
            header('Content-Length: ' . filesize($filepath));
            header('Cache-Control: no-cache, must-revalidate');
            header('Pragma: no-cache');
            header('Expires: 0');

            // Output file content
            readfile($filepath);
            unlink($filepath); // Delete the file after sending
            
            return ['success' => true, 'message' => 'Report generated successfully', 'total_votes_cast' => $totalVotes];
        } else {
            throw new Exception("Invalid format specified");
        }

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
    $groupedCandidates = [];
    foreach ($data['candidate_results'] as $candidate) {
        $dept = $candidate['department'];
        $pos = $candidate['position'];
        $name = $candidate['name'];
        if (!isset($groupedCandidates[$dept])) {
            $groupedCandidates[$dept] = [];
        }
        if (!isset($groupedCandidates[$dept][$pos])) {
            $groupedCandidates[$dept][$pos] = [];
        }
        if (!isset($groupedCandidates[$dept][$pos][$name])) {
            $groupedCandidates[$dept][$pos][$name] = 0;
        }
        $groupedCandidates[$dept][$pos][$name] += (int)$candidate['votes'];
    }
    foreach ($groupedCandidates as $dept => $positions) {
        $report .= "\n{$dept}\n" . str_repeat('-', strlen($dept)) . "\n";
        foreach ($positions as $pos => $cands) {
            $report .= "\n{$pos}:\n";
            foreach ($cands as $name => $votes) {
                $report .= "{$name} - {$votes} votes\n";
            }
        }
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
    $groupedCandidates = [];
    foreach ($data['candidate_results'] as $candidate) {
        $dept = $candidate['department'];
        $pos = $candidate['position'];
        $name = $candidate['name'];
        if (!isset($groupedCandidates[$dept])) {
            $groupedCandidates[$dept] = [];
        }
        if (!isset($groupedCandidates[$dept][$pos])) {
            $groupedCandidates[$dept][$pos] = [];
        }
        if (!isset($groupedCandidates[$dept][$pos][$name])) {
            $groupedCandidates[$dept][$pos][$name] = 0;
        }
        $groupedCandidates[$dept][$pos][$name] += (int)$candidate['votes'];
    }
    foreach ($groupedCandidates as $dept => $positions) {
        $html .= '<h4>' . $dept . '</h4>';
        foreach ($positions as $pos => $cands) {
            $html .= '<h5>' . $pos . ':</h5>';
            foreach ($cands as $name => $votes) {
                $html .= '<p>' . $name . ' - ' . $votes . ' votes</p>';
            }
        }
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
