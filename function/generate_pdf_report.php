<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../src/connection.php';
require_once '../src/check_session.php';

// Check if TCPDF is available
$vendorAutoload = __DIR__ . '/../vendor/autoload.php';
if (!file_exists($vendorAutoload)) {
    die(json_encode(['success' => false, 'message' => 'TCPDF library not found. Please install it using Composer.']));
}
require_once $vendorAutoload;

// Verify TCPDF class exists
if (!class_exists('TCPDF')) {
    die(json_encode(['success' => false, 'message' => 'TCPDF class not found after loading autoload.php']));
}

function generatePDFReport($format = 'pdf', $startDate = null, $endDate = null) {
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

        // Create new PDF document
        $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);

        // Set document information
        $pdf->SetCreator('Election System');
        $pdf->SetAuthor('Election System');
        $pdf->SetTitle('Election Report');

        // Remove default header/footer
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);

        // Set default monospaced font
        $pdf->SetDefaultMonospacedFont('courier');

        // Set margins
        $pdf->SetMargins(15, 15, 15);
        $pdf->SetHeaderMargin(5);
        $pdf->SetFooterMargin(10);

        // Set auto page breaks
        $pdf->SetAutoPageBreak(TRUE, 15);

        // Set image scale factor
        $pdf->setImageScale(1.25);

        // Add a page
        $pdf->AddPage();

        // Set font
        $pdf->SetFont('helvetica', '', 12);

        // Report content
        $html = '<h1 style="text-align:center;color:#0d6efd;">ELECTION REPORT</h1>';
        $html .= '<hr style="border:1px solid #0d6efd;">';

        // Report Period
        if ($startDate && $endDate) {
            $html .= '<h3>Report Period:</h3>';
            $html .= '<p>From: ' . date('F j, Y', strtotime($startDate)) . '</p>';
            $html .= '<p>To: ' . date('F j, Y', strtotime($endDate)) . '</p><br>';
        }

        // Election Periods
        $html .= '<h3>Election Periods:</h3>';
        foreach ($electionDates as $period) {
            $html .= '<p>Start: ' . date('F j, Y g:i A', strtotime($period['start_date'])) . '</p>';
            $html .= '<p>End: ' . date('F j, Y g:i A', strtotime($period['end_date'])) . '</p>';
            $html .= '<p>Results Date: ' . date('F j, Y g:i A', strtotime($period['results_date'])) . '</p><br>';
        }

        // Voter Statistics
        $html .= '<h3>Voter Statistics:</h3>';
        $html .= '<p>Total Registered Voters: ' . $totalRegisteredVoters . '</p>';
        $html .= '<p>Total Votes Cast: ' . $totalVotes . '</p>';
        $turnout = $totalRegisteredVoters > 0 ? 
            round(($totalVotes / $totalRegisteredVoters) * 100, 2) : 0;
        $html .= '<p>Voter Turnout: ' . $turnout . '%</p><br>';

        // Department Statistics
        $html .= '<h3>Department Statistics:</h3>';
        foreach ($departmentStats as $dept) {
            $html .= '<p>' . htmlspecialchars($dept['department']) . ': ' . $dept['total_voters'] . ' registered voters</p>';
        }
        $html .= '<br>';

        // Candidate Results
        $html .= '<h3>Candidate Results:</h3>';
        $currentDepartment = '';
        $currentPosition = '';
        $groupedCandidates = [];
        // Group candidates by department, position, and name, summing votes
        foreach ($candidates as $candidate) {
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
            $html .= '<h4>' . htmlspecialchars($dept) . '</h4>';
            foreach ($positions as $pos => $cands) {
                $html .= '<h5>' . htmlspecialchars($pos) . ':</h5>';
                foreach ($cands as $name => $votes) {
                    $html .= '<p>' . htmlspecialchars($name) . ' - ' . $votes . ' votes</p>';
                }
            }
        }

        // List of Voters
        $html .= '<h3>List of Voters:</h3>';
        $html .= '<ul style="margin-left:20px;">';
        $votedUsersQuery = "
            SELECT DISTINCT u.user_id, up.full_name, up.program_name
            FROM vote v
            JOIN user u ON v.user_id = u.user_id
            JOIN user_profile up ON u.user_id = up.user_id
            ORDER BY up.full_name ASC
        ";
        $votedUsersResult = $con->query($votedUsersQuery);
        while ($row = $votedUsersResult->fetch_assoc()) {
            $html .= '<li>' . htmlspecialchars($row['full_name']) . ' (' . htmlspecialchars($row['program_name']) . ')</li>';
        }
        $html .= '</ul><br>';

        // Set timezone to Asia/Manila and get current time
        date_default_timezone_set('Asia/Manila');
        $currentTime = new DateTime();
        $currentTime->modify('-3 hours');
        $html .= '<hr style="border:1px solid #0d6efd;">';
        $html .= '<p style="text-align:center;">Report generated at: ' . $currentTime->format('F j, Y g:i A') . ' (Philippine Time)</p>';
        $html .= '<p style="text-align:center;">Generated Format: PDF</p>';

        // Output the HTML content
        $pdf->writeHTML($html, true, false, true, false, '');

        // Create reports directory if it doesn't exist
        $reportsDir = __DIR__ . '/../reports';
        if (!file_exists($reportsDir)) {
            mkdir($reportsDir, 0777, true);
        }

        // Generate filename
        $timestamp = date('Y-m-d_H-i-s');
        $dateRange = ($startDate && $endDate) ? '_' . date('Y-m-d', strtotime($startDate)) . '_to_' . date('Y-m-d', strtotime($endDate)) : '';
        $filename = "election_report{$dateRange}_{$timestamp}.pdf";
        $filepath = "$reportsDir/$filename";

        // Output PDF file
        $pdf->Output($filepath, 'F');

        // Check if file was created successfully
        if (!file_exists($filepath)) {
            throw new Exception("Failed to create PDF file");
        }

        // Clear any previous output
        if (ob_get_level()) {
            ob_end_clean();
        }

        // Set headers for PDF download
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . basename($filepath) . '"');
        header('Content-Length: ' . filesize($filepath));
        header('Cache-Control: private, max-age=0, must-revalidate');
        header('Pragma: public');

        // Read and output file
        readfile($filepath);

        // Delete the file after sending
        unlink($filepath);
        exit;

        return ['success' => true, 'message' => 'PDF report generated successfully'];

    } catch (Exception $e) {
        error_log("PDF Report generation error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Error generating PDF report: ' . $e->getMessage()];
    }
}

// Handle POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $format = $_POST['format'] ?? 'pdf';
    $startDate = $_POST['start_date'] ?? null;
    $endDate = $_POST['end_date'] ?? null;

    if ($format !== 'pdf') {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Only PDF format is supported']);
        exit;
    }

    if ($startDate && $endDate) {
        if (!strtotime($startDate) || !strtotime($endDate)) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Invalid date format']);
            exit;
        }
        if (strtotime($startDate) > strtotime($endDate)) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Start date cannot be after end date']);
            exit;
        }
    }

    try {
        $result = generatePDFReport($format, $startDate, $endDate);
        if (!$result['success']) {
            header('Content-Type: application/json');
            echo json_encode($result);
        }
    } catch (Exception $e) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Error generating PDF: ' . $e->getMessage()]);
    }
    exit;
}
