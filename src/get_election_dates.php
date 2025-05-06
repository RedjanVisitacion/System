<?php
require_once 'check_session.php';
require_once 'connection.php';

$stmt = $con->prepare("SELECT start_date, end_date, results_date FROM election_dates LIMIT 1");
$stmt->execute();
$result = $stmt->get_result();
$dates = $result->fetch_assoc();
$stmt->close();

if ($dates) {
    echo json_encode([
        'success' => true,
        'start_date' => date('F d, Y h:i A', strtotime($dates['start_date'])),
        'end_date' => date('F d, Y h:i A', strtotime($dates['end_date'])),
        'results_date' => date('F d, Y h:i A', strtotime($dates['results_date']))
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'No election dates set',
        'start_date' => 'Not set',
        'end_date' => 'Not set',
        'results_date' => 'Not set'
    ]);
}
?> 