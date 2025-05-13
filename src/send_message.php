<?php
session_start();
include_once 'connection.php';
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit();
}

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['receiver_id']) || !isset($data['message'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit();
}

$sender_id = $_SESSION['user_id'];
$receiver_id = $data['receiver_id'];
$message = trim($data['message']);

// Validate message
if (empty($message)) {
    echo json_encode(['success' => false, 'message' => 'Message cannot be empty']);
    exit();
}

// Check if receiver exists
$check_receiver = $con->prepare("SELECT user_id FROM user WHERE user_id = ?");
$check_receiver->bind_param("s", $receiver_id);
$check_receiver->execute();
$result = $check_receiver->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Receiver not found']);
    exit();
}

// Insert message
$stmt = $con->prepare("INSERT INTO messages (sender_id, receiver_id, message) VALUES (?, ?, ?)");
$stmt->bind_param("sss", $sender_id, $receiver_id, $message);

if ($stmt->execute()) {
    echo json_encode([
        'success' => true, 
        'message' => 'Message sent successfully',
        'data' => [
            'message_id' => $con->insert_id,
            'sender_id' => $sender_id,
            'receiver_id' => $receiver_id,
            'message' => $message,
            'created_at' => date('Y-m-d H:i:s')
        ]
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to send message']);
}

$stmt->close();
$con->close();
?> 