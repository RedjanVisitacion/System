<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Prevent any output before headers
if (ob_get_length()) ob_end_clean();

require_once 'connection.php';
require_once 'check_session.php';
require_once __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\SimpleType\Jc;

// Set timezone to Philippines
date_default_timezone_set('Asia/Manila');

// Function to send JSON response
function sendJsonResponse($success, $message) {
    if (ob_get_length()) ob_end_clean();
    header('Content-Type: application/json');
    echo json_encode([
        'success' => $success,
        'message' => $message
    ]);
    exit;
}

// Function to generate DOCX report
function generateDOCXReport($format, $startDate = null, $endDate = null) {
    global $con;
    
    try {
        // Create new PHPWord instance
        $phpWord = new PhpWord();
        
        // Set document properties
        $phpWord->getDocInfo()
            ->setCreator('Election System')
            ->setCompany('Your Organization')
            ->setTitle('Election Report')
            ->setDescription('Election Report Generated on ' . date('F d, Y h:i A'))
            ->setCategory('Election Reports')
            ->setLastModifiedBy('System')
            ->setCreated(time())
            ->setModified(time());

        // Add a section
        $section = $phpWord->addSection([
            'marginLeft' => 600,
            'marginRight' => 600,
            'marginTop' => 600,
            'marginBottom' => 600
        ]);

        // Add title
        $section->addText(
            'ELECTION REPORT',
            ['bold' => true, 'size' => 16],
            ['alignment' => Jc::CENTER]
        );
        $section->addTextBreak(1);

        // Add report period
        $section->addText(
            'Report Period: ' . ($startDate ? date('F d, Y', strtotime($startDate)) : 'All Time') . 
            ($endDate ? ' to ' . date('F d, Y', strtotime($endDate)) : ''),
            ['size' => 12],
            ['alignment' => Jc::CENTER]
        );
        $section->addTextBreak(2);

        // Get election periods
        $periodQuery = "SELECT * FROM election_periods";
        if ($startDate && $endDate) {
            $periodQuery .= " WHERE start_date BETWEEN ? AND ? OR end_date BETWEEN ? AND ?";
        }
        $stmt = $con->prepare($periodQuery);
        if ($startDate && $endDate) {
            $stmt->bind_param("ssss", $startDate, $endDate, $startDate, $endDate);
        }
        $stmt->execute();
        $periods = $stmt->get_result();

        // Add election periods section
        $section->addText('Election Periods', ['bold' => true, 'size' => 14]);
        $section->addTextBreak(1);

        if ($periods->num_rows > 0) {
            while ($period = $periods->fetch_assoc()) {
                $section->addText(
                    "• " . $period['period_name'] . " (" . 
                    date('F d, Y', strtotime($period['start_date'])) . " to " . 
                    date('F d, Y', strtotime($period['end_date'])) . ")",
                    ['size' => 12]
                );
            }
        } else {
            $section->addText('No election periods found in the specified date range.', ['italic' => true]);
        }
        $section->addTextBreak(2);

        // Get total registered voters
        $voterQuery = "SELECT COUNT(*) as total FROM voters";
        $stmt = $con->prepare($voterQuery);
        $stmt->execute();
        $totalVoters = $stmt->get_result()->fetch_assoc()['total'];

        // Get total votes cast
        $votesQuery = "SELECT COUNT(DISTINCT voter_id) as total FROM votes";
        if ($startDate && $endDate) {
            $votesQuery .= " WHERE timestamp BETWEEN ? AND ?";
        }
        $stmt = $con->prepare($votesQuery);
        if ($startDate && $endDate) {
            $stmt->bind_param("ss", $startDate, $endDate);
        }
        $stmt->execute();
        $totalVotes = $stmt->get_result()->fetch_assoc()['total'];

        // Add voter statistics
        $section->addText('Voter Statistics', ['bold' => true, 'size' => 14]);
        $section->addTextBreak(1);
        $section->addText("Total Registered Voters: " . $totalVoters, ['size' => 12]);
        $section->addText("Total Votes Cast: " . $totalVotes, ['size' => 12]);
        $section->addText(
            "Voter Turnout: " . ($totalVoters > 0 ? round(($totalVotes / $totalVoters) * 100, 2) : 0) . "%",
            ['size' => 12]
        );
        $section->addTextBreak(2);

        // Get department statistics
        $deptQuery = "SELECT d.department_name, COUNT(DISTINCT v.voter_id) as votes
                     FROM departments d
                     LEFT JOIN voters v ON d.department_id = v.department_id
                     LEFT JOIN votes vt ON v.voter_id = vt.voter_id";
        if ($startDate && $endDate) {
            $deptQuery .= " WHERE vt.timestamp BETWEEN ? AND ?";
        }
        $deptQuery .= " GROUP BY d.department_id";
        $stmt = $con->prepare($deptQuery);
        if ($startDate && $endDate) {
            $stmt->bind_param("ss", $startDate, $endDate);
        }
        $stmt->execute();
        $departments = $stmt->get_result();

        // Add department statistics
        $section->addText('Department Statistics', ['bold' => true, 'size' => 14]);
        $section->addTextBreak(1);
        if ($departments->num_rows > 0) {
            while ($dept = $departments->fetch_assoc()) {
                $section->addText(
                    "• " . $dept['department_name'] . ": " . $dept['votes'] . " votes",
                    ['size' => 12]
                );
            }
        } else {
            $section->addText('No department statistics available.', ['italic' => true]);
        }
        $section->addTextBreak(2);

        // Get candidate results
        $candidateQuery = "SELECT c.*, COUNT(v.vote_id) as vote_count
                          FROM candidates c
                          LEFT JOIN votes v ON c.candidate_id = v.candidate_id";
        if ($startDate && $endDate) {
            $candidateQuery .= " WHERE v.timestamp BETWEEN ? AND ?";
        }
        $candidateQuery .= " GROUP BY c.candidate_id ORDER BY vote_count DESC";
        $stmt = $con->prepare($candidateQuery);
        if ($startDate && $endDate) {
            $stmt->bind_param("ss", $startDate, $endDate);
        }
        $stmt->execute();
        $candidates = $stmt->get_result();

        // Add candidate results
        $section->addText('Candidate Results', ['bold' => true, 'size' => 14]);
        $section->addTextBreak(1);
        if ($candidates->num_rows > 0) {
            while ($candidate = $candidates->fetch_assoc()) {
                $section->addText(
                    "• " . $candidate['full_name'] . " (" . $candidate['position'] . "): " . 
                    $candidate['vote_count'] . " votes",
                    ['size' => 12]
                );
            }
        } else {
            $section->addText('No candidate results available.', ['italic' => true]);
        }
        $section->addTextBreak(2);

        // Add footer with generation timestamp
        $section->addText(
            'Report generated on ' . date('F d, Y h:i A'),
            ['italic' => true, 'size' => 10],
            ['alignment' => Jc::CENTER]
        );

        if (ob_get_length()) ob_end_clean();
        header('Content-Description: File Transfer');
        header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
        header('Content-Disposition: attachment; filename="election_report_' . date('Y-m-d_His') . '.docx"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');

        $writer = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
        $writer->save('php://output');
        exit;

    } catch (Exception $e) {
        sendJsonResponse(false, 'Error generating DOCX report: ' . $e->getMessage());
    }
}

// Handle POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if format is DOCX
    if (!isset($_POST['format']) || $_POST['format'] !== 'docx') {
        sendJsonResponse(false, 'Invalid format specified');
    }

    // Validate dates if provided
    $startDate = isset($_POST['start_date']) ? $_POST['start_date'] : null;
    $endDate = isset($_POST['end_date']) ? $_POST['end_date'] : null;

    if ($startDate && !strtotime($startDate)) {
        sendJsonResponse(false, 'Invalid start date format');
    }

    if ($endDate && !strtotime($endDate)) {
        sendJsonResponse(false, 'Invalid end date format');
    }

    if ($startDate && $endDate && strtotime($startDate) > strtotime($endDate)) {
        sendJsonResponse(false, 'Start date cannot be after end date');
    }

    try {
        generateDOCXReport('docx', $startDate, $endDate);
    } catch (Exception $e) {
        sendJsonResponse(false, 'Error generating report: ' . $e->getMessage());
    }
} else {
    sendJsonResponse(false, 'Invalid request method');
} 