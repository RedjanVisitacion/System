<?php
require_once 'connection.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['candidate_id'])) {
    $candidate_id = $_GET['candidate_id'];
    
    $stmt = $con->prepare("SELECT candidate_id, name, department, position, age, platform, photo FROM elecom_candidate WHERE candidate_id = ?");
    $stmt->bind_param("i", $candidate_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $candidate = $result->fetch_assoc();
        echo json_encode([
            'success' => true,
            'candidate' => $candidate
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Candidate not found'
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request'
    ]);
}
?> 