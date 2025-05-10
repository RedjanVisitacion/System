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
        // Validate date range
        if ($startDate && $endDate) {
            $startTimestamp = strtotime($startDate);
            $endTimestamp = strtotime($endDate);
            
            if ($startTimestamp === false || $endTimestamp === false) {
                throw new Exception("Invalid date format provided");
            }
            
            if ($startTimestamp > $endTimestamp) {
                throw new Exception("Start date cannot be after end date");
            }
        }

        // Get election dates with enhanced query
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
        if (!$stmt) {
            throw new Exception("Failed to prepare election dates query: " . $con->error);
        }
        if (!empty($params)) {
            if (!$stmt->bind_param($types, ...$params)) {
                throw new Exception("Failed to bind parameters for election dates query: " . $stmt->error);
            }
        }
        if (!$stmt->execute()) {
            throw new Exception("Failed to execute election dates query: " . $stmt->error);
        }
        $electionDates = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        if (empty($electionDates)) {
            return ['success' => false, 'message' => 'No election data found for the selected date range'];
        }

        // Get candidates with enhanced query including department and position details
        $candidateQuery = "
            SELECT 
                c.candidate_id, 
                c.name, 
                c.department, 
                c.position, 
                c.age, 
                c.platform,
                COALESCE(r.votes, 0) as votes,
                r.published_at,
                (SELECT COUNT(*) FROM vote v WHERE v.candidate_id = c.candidate_id) as total_votes_received
            FROM candidate c 
            LEFT JOIN result r ON c.candidate_id = r.candidate_id 
            ORDER BY c.department, c.position, r.votes DESC
        ";
        $stmt = $con->prepare($candidateQuery);
        if (!$stmt) {
            throw new Exception("Failed to prepare candidates query: " . $con->error);
        }
        if (!$stmt->execute()) {
            throw new Exception("Failed to execute candidates query: " . $stmt->error);
        }
        $candidates = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        // Get department statistics with enhanced query and error handling
        $deptQuery = "
            SELECT 
                u.department,
                COUNT(DISTINCT u.user_id) as total_voters
            FROM user u
            WHERE u.role = 'student'
            GROUP BY u.department
        ";
        
        $stmt = $con->prepare($deptQuery);
        if (!$stmt) {
            throw new Exception("Failed to prepare department stats query: " . $con->error);
        }
        if (!$stmt->execute()) {
            throw new Exception("Failed to execute department stats query: " . $stmt->error);
        }
        $departmentStats = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        // Get total votes with enhanced query
        $voteQuery = "
            SELECT 
                COUNT(DISTINCT user_id) as total_voters,
                SUM(CASE WHEN voted_at BETWEEN ? AND ? THEN 1 ELSE 0 END) as votes_in_period
            FROM vote
        ";
        
        if ($startDate && $endDate) {
            $stmt = $con->prepare($voteQuery);
            if (!$stmt) {
                throw new Exception("Failed to prepare total votes query: " . $con->error);
            }
            if (!$stmt->bind_param("ss", $startDate, $endDate)) {
                throw new Exception("Failed to bind parameters for total votes query: " . $stmt->error);
            }
        } else {
            $stmt = $con->prepare("SELECT COUNT(DISTINCT user_id) as total_voters FROM vote");
            if (!$stmt) {
                throw new Exception("Failed to prepare total votes query: " . $con->error);
            }
        }
        
        if (!$stmt->execute()) {
            throw new Exception("Failed to execute total votes query: " . $stmt->error);
        }
        $voteStats = $stmt->get_result()->fetch_assoc();
        $totalVotes = intval($voteStats['votes_in_period'] ?? $voteStats['total_voters']);

        // Get voting statistics
        if ($startDate && $endDate) {
            $votingStatsQuery = "
                SELECT 
                    DATE(voted_at) as vote_date,
                    COUNT(*) as votes_per_day
                FROM vote
                WHERE voted_at BETWEEN ? AND ?
                GROUP BY DATE(voted_at)
                ORDER BY vote_date
            ";
            $stmt = $con->prepare($votingStatsQuery);
            if (!$stmt) {
                throw new Exception("Failed to prepare voting stats query: " . $con->error);
            }
            if (!$stmt->bind_param("ss", $startDate, $endDate)) {
                throw new Exception("Failed to bind parameters for voting stats query: " . $stmt->error);
            }
            if (!$stmt->execute()) {
                throw new Exception("Failed to execute voting stats query: " . $stmt->error);
            }
            $votingStats = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        }

        $reportData = [
            'report_period' => [ 'start_date' => $startDate, 'end_date' => $endDate ],
            'election_periods' => $electionDates,
            'department_statistics' => $departmentStats,
            'candidate_results' => $candidates,
            'total_votes_cast' => $totalVotes,
            'voting_statistics' => $votingStats ?? [],
            'generated_at' => date('Y-m-d H:i:s')
        ];

        $reportsDir = '../reports';
        if (!file_exists($reportsDir)) {
            if (!mkdir($reportsDir, 0777, true)) {
                throw new Exception("Failed to create reports directory");
            }
        }

        $timestamp = date('Y-m-d_H-i-s');
        $dateRange = $startDate && $endDate ? '_' . date('Y-m-d', strtotime($startDate)) . '_to_' . date('Y-m-d', strtotime($endDate)) : '';
        $filename = "election_report{$dateRange}_{$timestamp}.{$format}";
        $filepath = "$reportsDir/$filename";

        if ($format === 'txt') {
            $textReport = generateTextReport($reportData);
            if (file_put_contents($filepath, $textReport) === false) {
                throw new Exception("Failed to write text report file");
            }
            header('Content-Type: text/plain');
            header('Content-Disposition: inline; filename="' . basename($filepath) . '"');
            header('Content-Length: ' . filesize($filepath));
            readfile($filepath);
            unlink($filepath);
        } else {
            throw new Exception("Invalid format specified");
        }

        return [ 
            'success' => true, 
            'message' => 'Report generated successfully', 
            'total_votes_cast' => $totalVotes,
            'department_count' => count($departmentStats),
            'candidate_count' => count($candidates)
        ];

    } catch (Exception $e) {
        error_log("Report generation error: " . $e->getMessage());
        return [ 'success' => false, 'message' => 'Error generating report: ' . $e->getMessage() ];
    }
}

function generateTextReport($data) {
    global $con;
    $report = "ELECTION REPORT\n";
    $report .= str_repeat("=", 50) . "\n\n";

    // Report Header
    if ($data['report_period']['start_date'] && $data['report_period']['end_date']) {
        $report .= "Report Period:\n";
        $report .= "From: " . date('F j, Y', strtotime($data['report_period']['start_date'])) . "\n";
        $report .= "To: " . date('F j, Y', strtotime($data['report_period']['end_date'])) . "\n\n";
    }

    // Election Periods
    $report .= "Election Periods:\n";
    $report .= str_repeat("-", 50) . "\n";
    foreach ($data['election_periods'] as $period) {
        $report .= "Start Date: " . date('F j, Y g:i A', strtotime($period['start_date'])) . "\n";
        $report .= "End Date: " . date('F j, Y g:i A', strtotime($period['end_date'])) . "\n";
        $report .= "Results Date: " . date('F j, Y g:i A', strtotime($period['results_date'])) . "\n\n";
    }

    // Department Statistics
    $report .= "Department Statistics:\n";
    $report .= str_repeat("-", 50) . "\n";
    $totalVoters = 0;
    foreach ($data['department_statistics'] as $dept) {
        $report .= sprintf("%-20s: %d registered voters\n", 
            $dept['department'], 
            $dept['total_voters']
        );
        $totalVoters += $dept['total_voters'];
    }
    $report .= "\nTotal Registered Voters: " . $totalVoters . "\n";

    // Voting Statistics
    if (!empty($data['voting_statistics'])) {
        $report .= "Voting Statistics:\n";
        $report .= str_repeat("-", 50) . "\n";
        foreach ($data['voting_statistics'] as $stat) {
            $report .= date('F j, Y', strtotime($stat['vote_date'])) . ": " . $stat['votes_per_day'] . " votes\n";
        }
        $report .= "\n";
    }

    // Candidate Results
    $report .= "Candidate Results:\n";
    $report .= str_repeat("-", 50) . "\n";
    $currentDepartment = '';
    $currentPosition = '';
    foreach ($data['candidate_results'] as $candidate) {
        if ($candidate['department'] !== $currentDepartment) {
            $currentDepartment = $candidate['department'];
            $report .= "\n" . strtoupper($currentDepartment) . "\n";
            $report .= str_repeat("-", strlen($currentDepartment)) . "\n";
            $currentPosition = '';
        }
        if ($candidate['position'] !== $currentPosition) {
            $currentPosition = $candidate['position'];
            $report .= "\n" . $currentPosition . ":\n";
        }
        $report .= sprintf("%-30s: %d votes\n", $candidate['name'], $candidate['votes']);
    }

    // Report Footer
    $report .= "\n" . str_repeat("=", 50) . "\n";
    $report .= "Report generated at: " . date('F j, Y g:i A', strtotime($data['generated_at'])) . "\n";
    $report .= "Total Votes Cast: " . $data['total_votes_cast'] . "\n";
    $report .= str_repeat("=", 50) . "\n";

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