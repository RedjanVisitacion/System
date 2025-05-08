<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set proper headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');
header('Access-Control-Allow-Credentials: true');

require_once 'check_session.php';
require_once 'connection.php';

// Check if user is an officer
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'officer') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

try {
    // Begin transaction
    $con->begin_transaction();

    // 1. Delete all votes from the vote table
    $stmt = $con->prepare("DELETE FROM vote");
    if (!$stmt) {
        throw new Exception("Error preparing DELETE statement for vote table: " . $con->error);
    }
    $stmt->execute();
    $stmt->close();

    // 2. Reset vote_status in user_profile table
    $stmt = $con->prepare("UPDATE user_profile SET vote_status = 'Not Already Voted' WHERE vote_status = 'Voted'");
    if (!$stmt) {
        throw new Exception("Error preparing UPDATE statement for user_profile table: " . $con->error);
    }
    $stmt->execute();
    $stmt->close();

    // 3. Clear results table
    $stmt = $con->prepare("DELETE FROM result");
    if (!$stmt) {
        throw new Exception("Error preparing DELETE statement for result table: " . $con->error);
    }
    $stmt->execute();
    $stmt->close();

    // 4. Reset any election status if needed
    $stmt = $con->prepare("UPDATE election_dates SET status = 'pending' WHERE status = 'completed'");
    if (!$stmt) {
        throw new Exception("Error preparing UPDATE statement for election_dates table: " . $con->error);
    }
    $stmt->execute();
    $stmt->close();

    // Commit transaction
    $con->commit();

    echo json_encode(['success' => true, 'message' => 'All votes and related data have been reset successfully']);
} catch (Exception $e) {
    // Rollback transaction on error
    if ($con->inTransaction()) {
        $con->rollback();
    }
    error_log("Reset votes error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Failed to reset votes: ' . $e->getMessage()]);
}

$con->close();
?> 