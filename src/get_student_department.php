<?php
require_once 'check_session.php';
require_once 'connection.php';

// Get user's program from user_profile
$user_id = $_SESSION['user_id'];
$stmt = $con->prepare("SELECT program_name FROM user_profile WHERE user_id = ?");
$stmt->bind_param("s", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user_data = $result->fetch_assoc();

if (!$user_data) {
    echo json_encode(['success' => false, 'message' => 'User profile not found']);
    exit;
}

$program = $user_data['program_name'];
$department = '';

// Map program to department
if (strpos($program, 'Bachelor of Technology and Livelihood Education') !== false) {
    $department = 'PAFE';
} elseif (strpos($program, 'Bachelor of Science in Information Technology') !== false) {
    $department = 'SITE';
} elseif (strpos($program, 'Bachelor in Food Processing and Technology') !== false) {
    $department = 'AFPROTECHS';
}

// All students can vote for USG candidates
$allowed_departments = ['USG'];
if ($department) {
    $allowed_departments[] = $department;
}

echo json_encode([
    'success' => true,
    'department' => $department,
    'allowed_departments' => $allowed_departments
]); 