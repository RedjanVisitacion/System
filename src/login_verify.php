<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
include_once 'connection.php';
header('Content-Type: application/json');

// Check if database connection is successful
if ($con->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed: ' . $con->connect_error]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    if (!$data) {
        echo json_encode(['success' => false, 'message' => 'No data received']);
        exit;
    }

    $userId = trim($con->real_escape_string($data['userId']));
    $password = trim($con->real_escape_string($data['password']));

    $sql = "SELECT * FROM user WHERE user_id = '$userId' AND password = '$password'";
    $result = $con->query($sql);

    if ($result && $result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['role'] = $user['role']; // Assuming you have a role column in your user table
        $_SESSION['logged_in'] = true;
        
        echo json_encode([
            'success' => true, 
            'message' => 'Login successful',
            'role' => $user['role']
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Account not found']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
$con->close();
?> 