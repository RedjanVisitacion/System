<?php

require_once 'check_session.php';
require_once 'connection.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');
header('Access-Control-Allow-Credentials: true');

error_log("Reset votes request received");

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'officer') {
    error_log("Unauthorized access attempt - Role: " . ($_SESSION['role'] ?? 'not set'));
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

try {
    $con->begin_transaction();
    error_log("Transaction started");

    // 1. Delete all votes
    $stmt = $con->prepare("DELETE FROM vote");
    if (!$stmt) throw new Exception("Failed DELETE vote: " . $con->error);
    $stmt->execute();
    $stmt->close();
    error_log("Votes deleted");

    // 2. Reset vote status (if tracked)
    $stmt = $con->prepare("UPDATE user SET department = NULL WHERE role = 'student'");
    if (!$stmt) throw new Exception("Failed UPDATE user: " . $con->error);
    $stmt->execute();
    $stmt->close();
    error_log("User vote status reset");

    // 3. Clear results
    $stmt = $con->prepare("DELETE FROM result");
    if (!$stmt) throw new Exception("Failed DELETE result: " . $con->error);
    $stmt->execute();
    $stmt->close();
    error_log("Results deleted");

    // 4. Reset election status
    $stmt = $con->prepare("UPDATE election_dates SET status = 'pending' WHERE status = 'completed'");
    if (!$stmt) throw new Exception("Failed UPDATE election_dates: " . $con->error);
    $stmt->execute();
    $stmt->close();
    error_log("Election status reset");

    $con->commit();
    error_log("Transaction committed");

    echo json_encode(['success' => true, 'message' => 'Votes and related data reset successfully']);

} catch (Exception $e) {
    $con->rollback();
    error_log("Transaction rolled back: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Reset failed: ' . $e->getMessage()]);
}

$con->close();
error_log("Database connection closed");
?>
