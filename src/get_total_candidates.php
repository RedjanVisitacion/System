<?php
require_once 'connection.php';
header('Content-Type: application/json');

$query = "SELECT COUNT(*) as total_candidates FROM elecom_candidate";
$result = $con->query($query);

if ($result) {
    $row = $result->fetch_assoc();
    echo json_encode(['success' => true, 'total' => $row['total_candidates']]);
} else {
    echo json_encode(['success' => false, 'message' => 'Error fetching total candidates']);
}

$con->close();
?>
