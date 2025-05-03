<?php
require_once 'connection.php';
header('Content-Type: application/json');

try {
    $stmt = $con->prepare("
        SELECT COUNT(*) AS total_voters 
        FROM user 
        JOIN user_profile ON user.user_id = user_profile.user_id
        WHERE user.role = 'student'
    ");
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $total_voters = $row['total_voters'];

    echo json_encode(['success' => true, 'total_voters' => $total_voters]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
?>
