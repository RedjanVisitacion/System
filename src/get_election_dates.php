<?php
require_once 'check_session.php';
require_once 'connection.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Read and decode JSON input
    $data = json_decode(file_get_contents("php://input"), true);

    if (isset($data['action']) && $data['action'] === 'update_dates') {
        $startDate = $data['start_date'];
        $endDate = $data['end_date'];
        $resultsDate = $data['results_date'];

        if (!$startDate || !$endDate || !$resultsDate) {
            echo json_encode(['success' => false, 'message' => 'Missing date fields.']);
            exit;
        }

        // Upsert the single record with id = 1
        $stmt = $con->prepare("
            INSERT INTO election_dates (id, start_date, end_date, results_date)
            VALUES (1, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                start_date = VALUES(start_date),
                end_date = VALUES(end_date),
                results_date = VALUES(results_date)
        ");

        if (!$stmt) {
            echo json_encode(['success' => false, 'message' => 'Database error: failed to prepare statement.']);
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
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid action.']);
        exit;
    }
}

// Handle GET request to fetch current dates
$stmt = $con->prepare("SELECT start_date, end_date, results_date FROM election_dates WHERE id = 1");
$stmt->execute();
$result = $stmt->get_result();
$dates = $result->fetch_assoc();
$stmt->close();

if ($dates) {
    echo json_encode([
        'success' => true,
        'start_date' => date('Y-m-d\TH:i', strtotime($dates['start_date'])),
        'end_date' => date('Y-m-d\TH:i', strtotime($dates['end_date'])),
        'results_date' => date('Y-m-d\TH:i', strtotime($dates['results_date']))
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'No election dates set.',
        'start_date' => '',
        'end_date' => '',
        'results_date' => ''
    ]);
}
?>
