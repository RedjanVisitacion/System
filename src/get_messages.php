<?php
session_start();
include_once 'connection.php';
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit();
}

$user_id = $_SESSION['user_id'];

// Get conversation partner if specified
$partner_id = isset($_GET['partner_id']) ? $_GET['partner_id'] : null;

// Prepare the query based on whether a specific conversation is requested
if ($partner_id) {
    // Get messages between two specific users
    $query = "SELECT m.*, 
              up_sender.full_name as sender_name,
              up_receiver.full_name as receiver_name
              FROM messages m
              LEFT JOIN user_profile up_sender ON m.sender_id = up_sender.user_id
              LEFT JOIN user_profile up_receiver ON m.receiver_id = up_receiver.user_id
              WHERE (m.sender_id = ? AND m.receiver_id = ?)
              OR (m.sender_id = ? AND m.receiver_id = ?)
              ORDER BY m.created_at ASC";
    
    $stmt = $con->prepare($query);
    $stmt->bind_param("ssss", $user_id, $partner_id, $partner_id, $user_id);
} else {
    // Get all conversations for the user
    $query = "SELECT DISTINCT 
              CASE 
                  WHEN m.sender_id = ? THEN m.receiver_id
                  ELSE m.sender_id
              END as partner_id,
              up.full_name as partner_name,
              (SELECT message FROM messages 
               WHERE ((sender_id = ? AND receiver_id = partner_id)
               OR (sender_id = partner_id AND receiver_id = ?))
               ORDER BY created_at DESC LIMIT 1) as last_message,
              (SELECT created_at FROM messages 
               WHERE ((sender_id = ? AND receiver_id = partner_id)
               OR (sender_id = partner_id AND receiver_id = ?))
               ORDER BY created_at DESC LIMIT 1) as last_message_time,
              (SELECT COUNT(*) FROM messages 
               WHERE sender_id = partner_id 
               AND receiver_id = ? 
               AND is_read = 0) as unread_count
              FROM messages m
              LEFT JOIN user_profile up ON up.user_id = CASE 
                  WHEN m.sender_id = ? THEN m.receiver_id
                  ELSE m.sender_id
              END
              WHERE m.sender_id = ? OR m.receiver_id = ?
              ORDER BY last_message_time DESC";
    
    $stmt = $con->prepare($query);
    $stmt->bind_param("sssssssss", $user_id, $user_id, $user_id, $user_id, $user_id, $user_id, $user_id, $user_id, $user_id);
}

$stmt->execute();
$result = $stmt->get_result();
$messages = [];

while ($row = $result->fetch_assoc()) {
    $messages[] = $row;
}

// Mark messages as read if viewing a specific conversation
if ($partner_id) {
    $update = $con->prepare("UPDATE messages SET is_read = 1 
                            WHERE sender_id = ? AND receiver_id = ? AND is_read = 0");
    $update->bind_param("ss", $partner_id, $user_id);
    $update->execute();
    $update->close();
}

echo json_encode([
    'success' => true,
    'data' => $messages
]);

$stmt->close();
$con->close();
?> 