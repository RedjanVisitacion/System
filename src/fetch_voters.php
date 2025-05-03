<?php
require_once 'connection.php';

header('Content-Type: application/json');

// DEBUG mode (remove in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

$query = "SELECT full_name FROM user_profile 
          JOIN user ON user_profile.user_id = user.user_id 
          WHERE user.role = 'student' 
          ORDER BY full_name ASC";

$result = $con->query($query);

if ($result) {
    $voters = [];

    while ($row = $result->fetch_assoc()) {
        $voters[] = ['full_name' => $row['full_name']]; // Key must match JavaScript usage
    }

    echo json_encode(['success' => true, 'voters' => $voters]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Query failed: ' . $con->error
    ]);
}

$con->close();
?>
