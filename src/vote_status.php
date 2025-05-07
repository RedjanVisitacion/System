<?php
require_once 'check_session.php';
require_once 'connection.php';
header('Content-Type: application/json');

// Check if user is a student
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'student') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

// Get user ID from session
$user_id = $_SESSION['user_id'];

// Check if user has already voted
$stmt = $con->prepare("SELECT COUNT(*) as vote_count FROM vote WHERE user_id = ? AND vote_status = 'Voted'");
$stmt->bind_param("s", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$vote_count = $result->fetch_assoc()['vote_count'];
$stmt->close();

if ($vote_count > 0) {
    echo json_encode(['success' => false, 'message' => 'You have already cast your vote.']);
    exit;
}

// Get the POST data
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['votes']) || !is_array($data['votes'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid vote data']);
    exit;
}

// Start transaction
$con->begin_transaction();

try {
    // First, insert 'Not Already Voted' status for all positions
    foreach ($data['votes'] as $vote) {
        if (!isset($vote['candidate_id']) || !isset($vote['position'])) {
            throw new Exception('Invalid vote data structure');
        }

        // Insert initial 'Not Already Voted' status
        $stmt = $con->prepare("INSERT INTO vote (user_id, candidate_id, vote_status) VALUES (?, ?, 'Not Already Voted')");
        $stmt->bind_param("si", $user_id, $vote['candidate_id']);
        
        if (!$stmt->execute()) {
            throw new Exception('Failed to record initial vote status');
        }
        $stmt->close();
    }

    // Then update all votes to 'Voted' status
    foreach ($data['votes'] as $vote) {
        $stmt = $con->prepare("UPDATE vote SET vote_status = 'Voted' WHERE user_id = ? AND candidate_id = ?");
        $stmt->bind_param("si", $user_id, $vote['candidate_id']);
        
        if (!$stmt->execute()) {
            throw new Exception('Failed to update vote status');
        }
        $stmt->close();
    }

    // Commit transaction
    $con->commit();
    echo json_encode(['success' => true, 'message' => 'Vote recorded successfully']);

} catch (Exception $e) {
    // Rollback transaction on error
    $con->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$con->close();
?>
