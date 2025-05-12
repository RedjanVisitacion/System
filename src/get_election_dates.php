<?php
require_once 'check_session.php';
require_once 'connection.php';

// Force timezone to Philippine Standard Time
date_default_timezone_set('Asia/Manila');

// Send JSON headers
header('Content-Type: application/json');

// POST: Update election dates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);

    if (isset($data['action']) && $data['action'] === 'update_dates') {
        $startDateRaw = $data['start_date'] ?? null;
        $endDateRaw = $data['end_date'] ?? null;
        $resultsDateRaw = $data['results_date'] ?? null;

        if (!$startDateRaw || !$endDateRaw || !$resultsDateRaw) {
            echo json_encode(['success' => false, 'message' => 'Missing date fields.']);
            exit;
        }

        try {
            // Convert input to PHP DateTime in Asia/Manila and format to SQL datetime
            $startDate = (new DateTime($startDateRaw, new DateTimeZone('Asia/Manila')))->format('Y-m-d H:i:s');
            $endDate = (new DateTime($endDateRaw, new DateTimeZone('Asia/Manila')))->format('Y-m-d H:i:s');
            $resultsDate = (new DateTime($resultsDateRaw, new DateTimeZone('Asia/Manila')))->format('Y-m-d H:i:s');
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Invalid date format.']);
            exit;
        }

        // Upsert into DB (Insert or Update if ID = 1 exists)
        $stmt = $con->prepare("
            INSERT INTO election_dates (id, start_date, end_date, results_date)
            VALUES (1, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                start_date = VALUES(start_date),
                end_date = VALUES(end_date),
                results_date = VALUES(results_date),
                updated_at = CURRENT_TIMESTAMP
        ");

        if (!$stmt) {
            echo json_encode(['success' => false, 'message' => 'Failed to prepare SQL statement.']);
            exit;
        }

        $stmt->bind_param('sss', $startDate, $endDate, $resultsDate);
        $success = $stmt->execute();
        $stmt->close();

        echo json_encode([
            'success' => $success,
            'message' => $success ? 'Election dates updated successfully.' : 'Failed to update election dates.'
        ]);
        exit;
    }

    echo json_encode(['success' => false, 'message' => 'Invalid action.']);
    exit;
}

// GET: Fetch current election dates
$stmt = $con->prepare("SELECT start_date, end_date, results_date FROM election_dates WHERE id = 1");
$stmt->execute();
$result = $stmt->get_result();
$dates = $result->fetch_assoc();
$stmt->close();

if ($dates) {
    echo json_encode([
        'success' => true,
        'start_date' => date('m/d/Y h:i A', strtotime($dates['start_date'])), // For display (12-hour format)
        'end_date' => date('m/d/Y h:i A', strtotime($dates['end_date'])),
        'results_date' => date('m/d/Y h:i A', strtotime($dates['results_date']))
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'No election dates found.',
        'start_date' => '',
        'end_date' => '',
        'results_date' => ''
    ]);
}
?>
