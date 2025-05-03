<?php
require_once 'check_session.php';
require_once 'connection.php';

header('Content-Type: application/json');

$query = "SELECT candidate_id, name, position, department, platform FROM candidate ORDER BY name ASC";
$result = $con->query($query);

if ($result) {
    $candidates = [];
    while ($row = $result->fetch_assoc()) {
        $candidates[] = $row;
    }

    echo json_encode([
        'success' => true,
        'candidates' => $candidates
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to fetch candidates.'
    ]);
}
?>
