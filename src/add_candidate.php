<?php
// Enable error reporting for debugging (remove in production)
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Include database connection
include_once 'connection.php';

// Set header to return JSON response
header('Content-Type: application/json');

// Function to send JSON response
function sendResponse($success, $message, $data = null) {
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ]);
    exit;
}

// Check if request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendResponse(false, 'Invalid request method');
}

// Get and sanitize candidate name from POST
$name = trim($_POST['name'] ?? '');

// Validate input
if (empty($name)) {
    sendResponse(false, 'Candidate name cannot be empty');
}

if (strlen($name) > 255) {
    sendResponse(false, 'Candidate name is too long');
}

try {
    // Prepare and execute the insert statement
    $stmt = $con->prepare("INSERT INTO elecom_candidate (name) VALUES (?)");
    if (!$stmt) {
        throw new Exception("Failed to prepare statement: " . $con->error);
    }

    $stmt->bind_param("s", $name);
    
    if ($stmt->execute()) {
        sendResponse(true, 'Candidate added successfully', ['id' => $con->insert_id]);
    } else {
        throw new Exception("Failed to execute statement: " . $stmt->error);
    }
} catch (Exception $e) {
    sendResponse(false, 'Error: ' . $e->getMessage());
} finally {
    // Clean up
    if (isset($stmt)) {
        $stmt->close();
    }
    if (isset($con)) {
        $con->close();
    }
} 