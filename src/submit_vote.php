<?php
require_once 'check_session.php';
require_once 'connection.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$user_id = $_SESSION['user_id'];
$candidate_id = $_POST['candidate_id'] ?? null;

if (!$candidate_id) {
    echo json_encode(['success' => false, 'message' => 'Candidate ID is required']);
    exit;
}

// Get student's program
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
$allowed_departments = ['USG']; // All students can vote for USG

// Map program to department
if (strpos($program, 'Bachelor of Technology and Livelihood Education') !== false) {
    $allowed_departments[] = 'PAFE';
} elseif (strpos($program, 'Bachelor of Science in Information Technology') !== false) {
    $allowed_departments[] = 'SITE';
} elseif (strpos($program, 'Bachelor in Food Processing and Technology') !== false) {
    $allowed_departments[] = 'AFPROTECHS';
}

// Check if candidate is from an allowed department
$stmt = $con->prepare("SELECT d.department_name 
                      FROM candidate c 
                      JOIN department d ON c.department_id = d.department_id 
                      WHERE c.candidate_id = ?");
$stmt->bind_param("i", $candidate_id);
$stmt->execute();
$result = $stmt->get_result();
$candidate_data = $result->fetch_assoc();

if (!$candidate_data || !in_array($candidate_data['department_name'], $allowed_departments)) {
    echo json_encode(['success' => false, 'message' => 'You are not eligible to vote for this candidate']);
    exit;
}

// Check if user has already voted
$stmt = $con->prepare("SELECT COUNT(*) as vote_count FROM vote WHERE user_id = ?");
$stmt->bind_param("s", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$vote_data = $result->fetch_assoc();

if ($vote_data['vote_count'] > 0) {
    echo json_encode(['success' => false, 'message' => 'You have already voted']);
    exit;
}

// Record the vote
$stmt = $con->prepare("INSERT INTO vote (user_id, candidate_id, vote_status) VALUES (?, ?, 'Voted')");
$stmt->bind_param("si", $user_id, $candidate_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Vote recorded successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to record vote']);
}

$con->close(); 