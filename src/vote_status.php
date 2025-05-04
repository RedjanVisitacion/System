<?php
require_once 'connection.php';
header('Content-Type: application/json');

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Assuming we are getting the user_id and candidate_id from the request
$user_id = $_POST['user_id']; // replace with actual user ID
$candidate_id = $_POST['candidate_id']; // replace with actual candidate ID

// Check if the user has already voted
$query = "SELECT vote_status FROM vote WHERE user_id = ? AND candidate_id = ?";
$stmt = $con->prepare($query);
$stmt->bind_param('si', $user_id, $candidate_id);
$stmt->execute();
$stmt->store_result();

// If the user has already voted, return an error message
if ($stmt->num_rows > 0) {
    // Fetch the current vote status
    $stmt->bind_result($vote_status);
    $stmt->fetch();

    if ($vote_status === 'Voted') {
        echo json_encode(['success' => false, 'message' => 'You have already voted.']);
    } else {
        // If the user has not voted yet, insert or update the vote
        $insert_query = "INSERT INTO vote (user_id, candidate_id, vote_status) VALUES (?, ?, 'Voted')";
        $stmt_insert = $con->prepare($insert_query);
        $stmt_insert->bind_param('si', $user_id, $candidate_id);

        if ($stmt_insert->execute()) {
            echo json_encode(['success' => true, 'message' => 'Vote recorded successfully.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to record vote.']);
        }
    }
} else {
    // If no record is found, insert a new vote entry
    $insert_query = "INSERT INTO vote (user_id, candidate_id, vote_status) VALUES (?, ?, 'Not Already Voted')";
    $stmt_insert = $con->prepare($insert_query);
    $stmt_insert->bind_param('si', $user_id, $candidate_id);

    if ($stmt_insert->execute()) {
        echo json_encode(['success' => true, 'message' => 'Vote recorded successfully.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to record vote.']);
    }
}

$stmt->close();
$con->close();
?>
