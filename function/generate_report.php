<?php
require_once '../src/connection.php';
require_once '../src/check_session.php';

// Function to generate election report
function generateElectionReport($format = 'json') {
    global $con;
    
    try {
        // Get election dates
        $stmt = $con->prepare("SELECT * FROM election_dates ORDER BY id DESC LIMIT 1");
        $stmt->execute();
        $electionDates = $stmt->get_result()->fetch_assoc();
        
        // Get all candidates with their results
        $stmt = $con->prepare("
            SELECT 
                c.candidate_id,
                c.name,
                c.department,
                c.position,
                c.age,
                c.platform,
                COALESCE(r.votes, 0) as votes
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
        
        // Get total votes cast
        $stmt = $con->prepare("SELECT COUNT(DISTINCT user_id) as total_votes FROM vote");
        $stmt->execute();
        $totalVotes = $stmt->get_result()->fetch_assoc()['total_votes'];
        
        // Compile report data
        $reportData = [
            'election_period' => [
                'start_date' => $electionDates['start_date'],
                'end_date' => $electionDates['end_date'],
                'results_date' => $electionDates['results_date']
            ],
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
        
        // Generate filename with timestamp
        $timestamp = date('Y-m-d_H-i-s');
        $filename = "election_report_{$timestamp}.{$format}";
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
    
    // Election Period
    $report .= "Election Period:\n";
    $report .= "Start Date: " . date('F j, Y g:i A', strtotime($data['election_period']['start_date'])) . "\n";
    $report .= "End Date: " . date('F j, Y g:i A', strtotime($data['election_period']['end_date'])) . "\n";
    $report .= "Results Date: " . date('F j, Y g:i A', strtotime($data['election_period']['results_date'])) . "\n\n";
    
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
    if (!in_array($format, ['json', 'txt'])) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid format specified'
        ]);
        exit;
    }
    
    $result = generateElectionReport($format);
    echo json_encode($result);
    exit;
}
?> 