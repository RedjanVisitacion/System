<?php
require_once '../src/connection.php';
require_once '../src/check_session.php';

// Function to generate election report
function generateElectionReport($format = 'json', $startDate = null, $endDate = null) {
    global $con;
    
    try {
        // Base query for election dates
        $dateQuery = "SELECT * FROM election_dates";
        $params = [];
        $types = "";
        
        // Add date range conditions if provided
        if ($startDate && $endDate) {
            $dateQuery .= " WHERE start_date >= ? AND end_date <= ?";
            $params[] = $startDate;
            $params[] = $endDate;
            $types .= "ss";
        }
        
        $dateQuery .= " ORDER BY id DESC";
        
        // Get election dates
        $stmt = $con->prepare($dateQuery);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $electionDates = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        if (empty($electionDates)) {
            return [
                'success' => false,
                'message' => 'No election data found for the selected date range'
            ];
        }
        
        // Get all candidates with their results
        $stmt = $con->prepare("
            SELECT 
                c.candidate_id,
                c.name,
                c.department,
                c.position,
                c.age,
                c.platform,
                COALESCE(r.votes, 0) as votes,
                r.published_at
            FROM candidate c
            LEFT JOIN result r ON c.candidate_id = r.candidate_id
            ORDER BY c.department, c.position, r.votes DESC
        ");
        $stmt->execute();
        $candidates = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        // Get total votes per department
        $stmt = $con->prepare("
            SELECT 
                department,
                COUNT(DISTINCT user_id) as total_voters
            FROM user
            WHERE role = 'student'
            GROUP BY department
        ");
        $stmt->execute();
        $departmentStats = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        // Get total votes cast with date range
        $voteQuery = "SELECT COUNT(DISTINCT user_id) as total_votes FROM vote";
        if ($startDate && $endDate) {
            $voteQuery .= " WHERE vote_timestamp BETWEEN ? AND ?";
        }
        
        $stmt = $con->prepare($voteQuery);
        if ($startDate && $endDate) {
            $stmt->bind_param("ss", $startDate, $endDate);
        }
        $stmt->execute();
        $totalVotes = $stmt->get_result()->fetch_assoc()['total_votes'];
        
        // Compile report data
        $reportData = [
            'report_period' => [
                'start_date' => $startDate,
                'end_date' => $endDate
            ],
            'election_periods' => $electionDates,
            'department_statistics' => $departmentStats,
            'candidate_results' => $candidates,
            'total_votes_cast' => $totalVotes,
            'generated_at' => date('Y-m-d H:i:s')
        ];
        
        // Create reports directory if it doesn't exist
        $reportsDir = '../reports';
        if (!file_exists($reportsDir)) {
            mkdir($reportsDir, 0777, true);
        }
        
        // Generate filename with timestamp and date range
        $timestamp = date('Y-m-d_H-i-s');
        $dateRange = $startDate && $endDate ? 
            '_' . date('Y-m-d', strtotime($startDate)) . '_to_' . date('Y-m-d', strtotime($endDate)) : '';
        $filename = "election_report{$dateRange}_{$timestamp}.{$format}";
        $filepath = "{$reportsDir}/{$filename}";
        
        // Write report to file
        if ($format === 'json') {
            file_put_contents($filepath, json_encode($reportData, JSON_PRETTY_PRINT));
        } else if ($format === 'txt') {
            $textReport = generateTextReport($reportData);
            file_put_contents($filepath, $textReport);
        }
        
        return [
            'success' => true,
            'message' => 'Report generated successfully',
            'filepath' => $filepath
        ];
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'Error generating report: ' . $e->getMessage()
        ];
    }
}

// Function to generate text format report
function generateTextReport($data) {
    $report = "ELECTION REPORT\n";
    $report .= "==============\n\n";
    
    // Report Period
    if ($data['report_period']['start_date'] && $data['report_period']['end_date']) {
        $report .= "Report Period:\n";
        $report .= "From: " . date('F j, Y', strtotime($data['report_period']['start_date'])) . "\n";
        $report .= "To: " . date('F j, Y', strtotime($data['report_period']['end_date'])) . "\n\n";
    }
    
    // Election Periods
    $report .= "Election Periods:\n";
    $report .= "================\n";
    foreach ($data['election_periods'] as $period) {
        $report .= "Start Date: " . date('F j, Y g:i A', strtotime($period['start_date'])) . "\n";
        $report .= "End Date: " . date('F j, Y g:i A', strtotime($period['end_date'])) . "\n";
        $report .= "Results Date: " . date('F j, Y g:i A', strtotime($period['results_date'])) . "\n\n";
    }
    
    // Department Statistics
    $report .= "Department Statistics:\n";
    $report .= "====================\n";
    foreach ($data['department_statistics'] as $dept) {
        $report .= "{$dept['department']}: {$dept['total_voters']} registered voters\n";
    }
    $report .= "\n";
    
    // Total Votes Cast
    $report .= "Total Votes Cast: {$data['total_votes_cast']}\n\n";
    
    // Candidate Results
    $report .= "Candidate Results:\n";
    $report .= "=================\n";
    $currentDepartment = '';
    $currentPosition = '';
    
    foreach ($data['candidate_results'] as $candidate) {
        if ($candidate['department'] !== $currentDepartment) {
            $currentDepartment = $candidate['department'];
            $report .= "\n{$currentDepartment}\n";
            $report .= str_repeat('-', strlen($currentDepartment)) . "\n";
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

// Handle AJAX request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    $format = $_POST['format'] ?? 'json';
    $startDate = $_POST['start_date'] ?? null;
    $endDate = $_POST['end_date'] ?? null;
    
    if (!in_array($format, ['json', 'txt'])) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid format specified'
        ]);
        exit;
    }
    
    // Validate dates if provided
    if ($startDate && $endDate) {
        if (!strtotime($startDate) || !strtotime($endDate)) {
            echo json_encode([
                'success' => false,
                'message' => 'Invalid date format'
            ]);
            exit;
        }
        
        if (strtotime($startDate) > strtotime($endDate)) {
            echo json_encode([
                'success' => false,
                'message' => 'Start date cannot be after end date'
            ]);
            exit;
        }
    }
    
    $result = generateElectionReport($format, $startDate, $endDate);
    echo json_encode($result);
    exit;
}
?> 