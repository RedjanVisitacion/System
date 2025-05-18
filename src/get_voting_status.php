<?php
require_once 'check_session.php';
require_once 'connection.php';
header('Content-Type: application/json');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please log in to continue']);
    exit;
}

// Get user ID from session
$user_id = $_SESSION['user_id'];

try {
    // First check if user exists and get their role
    $stmt = $con->prepare("SELECT u.role, up.full_name 
                          FROM elecom_user u 
                          LEFT JOIN elecom_user_profile up ON u.user_id = up.user_id 
                          WHERE u.user_id = ?");
    if (!$stmt) {
        throw new Exception("Database prepare error: " . $con->error);
    }
    
    $stmt->bind_param("s", $user_id);
    if (!$stmt->execute()) {
        throw new Exception("Database execute error: " . $stmt->error);
    }
    
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();

    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit;
    }

    // Check if user is a student
    if ($user['role'] !== 'student') {
        echo json_encode([
            'success' => false, 
            'message' => 'Only students can vote. Your account type is: ' . $user['role']
        ]);
        exit;
    }

    // Check if user has already voted
    $stmt = $con->prepare("SELECT COUNT(*) as vote_count FROM elecom_vote WHERE user_id = ? AND vote_status = 'Voted'");
    if (!$stmt) {
        throw new Exception("Database prepare error: " . $con->error);
    }
    
    $stmt->bind_param("s", $user_id);
    if (!$stmt->execute()) {
        throw new Exception("Database execute error: " . $stmt->error);
    }
    
    $result = $stmt->get_result();
    $vote_count = $result->fetch_assoc()['vote_count'];
    $stmt->close();

    // Check election dates
    $stmt = $con->prepare("SELECT start_date, end_date FROM elecom_election_dates WHERE id = 1");
    if (!$stmt) {
        throw new Exception("Database prepare error: " . $con->error);
    }
    
    if (!$stmt->execute()) {
        throw new Exception("Database execute error: " . $stmt->error);
    }
    
    $result = $stmt->get_result();
    $dates = $result->fetch_assoc();
    $stmt->close();

    if (!$dates) {
        echo json_encode([
            'success' => false,
            'message' => 'Election dates not set'
        ]);
        exit;
    }

    $now = new DateTime();
    $startDate = new DateTime($dates['start_date']);
    $endDate = new DateTime($dates['end_date']);

    $response = [
        'success' => true,
        'hasVoted' => $vote_count > 0,
        'userInfo' => [
            'name' => $user['full_name'],
            'role' => $user['role']
        ],
        'electionStatus' => [
            'isActive' => $now >= $startDate && $now <= $endDate,
            'startDate' => $dates['start_date'],
            'endDate' => $dates['end_date']
        ]
    ];

    echo json_encode($response);

} catch (Exception $e) {
    error_log("Error in get_voting_status.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error checking voting status: ' . $e->getMessage()
    ]);
}

$con->close();
?> 