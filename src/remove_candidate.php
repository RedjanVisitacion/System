<?php
require_once 'check_session.php';
require_once 'connection.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['candidate_id'])) {

    $candidateIds = $_POST['candidate_id'];

    // Ensure $candidateIds is an array
    if (!is_array($candidateIds)) {
        echo json_encode(['success' => false, 'message' => 'Invalid input format.']);
        exit;
    }

    // Filter and sanitize the IDs to allow only digits
    $filteredIds = array_filter($candidateIds, fn($id) => ctype_digit($id));
    
    if (empty($filteredIds)) {
        echo json_encode(['success' => false, 'message' => 'No valid candidate IDs provided.']);
        exit;
    }

    // Create a dynamic SQL query with placeholders
    $placeholders = implode(',', array_fill(0, count($filteredIds), '?'));
    $types = str_repeat('i', count($filteredIds)); // all IDs are integers

    $stmt = $con->prepare("DELETE FROM candidate WHERE candidate_id IN ($placeholders)");
    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'Failed to prepare statement.']);
        exit;
    }

    // Bind parameters dynamically
    $stmt->bind_param($types, ...$filteredIds);

    if ($stmt->execute()) {
        $deletedCount = $stmt->affected_rows;
        if ($deletedCount > 0) {
            echo json_encode(['success' => true, 'message' => "$deletedCount candidate(s) removed successfully."]);
        } else {
            echo json_encode(['success' => false, 'message' => 'No candidates were removed (not found).']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to execute removal.']);
    }

    $stmt->close();
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid request.']);
exit;
?>
