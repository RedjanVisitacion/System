<?php
require_once 'check_session.php';
require_once 'connection.php';

header('Content-Type: application/json');

// Check if it's an AJAX request
if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Get the raw POST data
$json = file_get_contents('php://input');
$data = json_decode($json, true);

// Validate input
if (!isset($data['votes'], $data['department']) || !is_array($data['votes']) || empty($data['votes'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid vote data']);
    exit;
}

try {
    $con->begin_transaction();
    $user_id = $_SESSION['user_id'];

    // Check if user has already voted
    $check_stmt = $con->prepare("SELECT COUNT(*) as vote_count FROM elecom_vote WHERE user_id = ?");
    $check_stmt->bind_param("s", $user_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    $vote_count = $result->fetch_assoc()['vote_count'];
    $check_stmt->close();

    if ($vote_count > 0) {
        throw new Exception('You have already voted.');
    }

    // Prepare vote insert
    $vote_stmt = $con->prepare("INSERT INTO elecom_vote (user_id, candidate_id, vote_status) VALUES (?, ?, 'Voted')");

    // Prepare for getting position
    $position_stmt = $con->prepare("SELECT position FROM elecom_candidate WHERE candidate_id = ?");

    // Prepare for result insert/update
    $result_stmt = $con->prepare("INSERT INTO elecom_result (department, position, candidate_id, votes)
                                  VALUES (?, ?, ?, 1)
                                  ON DUPLICATE KEY UPDATE votes = votes + 1");

    foreach ($data['votes'] as $candidate_id) {
        // Insert vote
        $vote_stmt->bind_param("si", $user_id, $candidate_id);
        $vote_stmt->execute();

        // Get position
        $position_stmt->bind_param("i", $candidate_id);
        $position_stmt->execute();
        $pos_res = $position_stmt->get_result();
        $position_data = $pos_res->fetch_assoc();

        if (!$position_data) {
            throw new Exception("Candidate with ID $candidate_id not found.");
        }

        // Update result
        $result_stmt->bind_param("ssi", $data['department'], $position_data['position'], $candidate_id);
        $result_stmt->execute();
    }

    // Close prepared statements
    $vote_stmt->close();
    $position_stmt->close();
    $result_stmt->close();

    // Commit transaction
    $con->commit();

    echo json_encode(['success' => true, 'message' => 'Your vote has been submitted.']);
} catch (Exception $e) {
    $con->rollback();
    echo json_encode(['success' => false, 'message' => 'Vote submission failed: ' . $e->getMessage()]);
}

$con->close();
?>
