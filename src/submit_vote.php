<?php
require_once 'connection.php';
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit;
}

// Get user ID from session
$user_id = $_SESSION['user_id'];

// Get JSON data from request body
$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (!isset($data['votes']) || !is_array($data['votes'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid vote data']);
    exit;
}

try {
    // Start transaction
    $con->begin_transaction();

    // Check if user has already voted
    $checkVoteStmt = $con->prepare("SELECT COUNT(*) as vote_count FROM vote WHERE user_id = ?");
    $checkVoteStmt->bind_param("s", $user_id);
    $checkVoteStmt->execute();
    $voteResult = $checkVoteStmt->get_result();
    $voteCount = $voteResult->fetch_assoc()['vote_count'];

    if ($voteCount > 0) {
        throw new Exception('You have already voted');
    }

    // Get user's program to check department eligibility
    $userStmt = $con->prepare("
        SELECT up.program_name, u.department 
        FROM user_profile up 
        JOIN user u ON up.user_id = u.user_id 
        WHERE up.user_id = ?
    ");
    $userStmt->bind_param("s", $user_id);
    $userStmt->execute();
    $userResult = $userStmt->get_result();
    $userData = $userResult->fetch_assoc();

    // Map program to allowed departments
    $allowedDepartments = ['USG']; // All students can vote for USG
    if (strpos($userData['program_name'], 'BTLED') !== false) {
        $allowedDepartments[] = 'PAFE';
    } elseif (strpos($userData['program_name'], 'BSIT') !== false) {
        $allowedDepartments[] = 'SITE';
    } elseif (strpos($userData['program_name'], 'BFP') !== false) {
        $allowedDepartments[] = 'AFPROTECHS';
    }

    // Prepare vote insertion statement
    $voteStmt = $con->prepare("
        INSERT INTO vote (user_id, candidate_id, vote_status) 
        VALUES (?, ?, 'Voted')
    ");

    // Process each vote
    foreach ($data['votes'] as $candidateId) {
        // Verify candidate exists and belongs to allowed department
        $candidateStmt = $con->prepare("
            SELECT department, position 
            FROM candidate 
            WHERE candidate_id = ?
        ");
        $candidateStmt->bind_param("i", $candidateId);
        $candidateStmt->execute();
        $candidateResult = $candidateStmt->get_result();
        $candidateData = $candidateResult->fetch_assoc();

        if (!$candidateData) {
            throw new Exception('Invalid candidate selected');
        }

        // Check if candidate's department is allowed
        if (!in_array($candidateData['department'], $allowedDepartments)) {
            throw new Exception('You are not eligible to vote for this department');
        }

        // Insert vote
        $voteStmt->bind_param("si", $user_id, $candidateId);
        if (!$voteStmt->execute()) {
            throw new Exception('Error recording vote');
        }

        // Update result table
        $resultStmt = $con->prepare("
            INSERT INTO result (department, position, candidate_id, votes) 
            VALUES (?, ?, ?, 1)
            ON DUPLICATE KEY UPDATE votes = votes + 1
        ");
        $resultStmt->bind_param("ssi", 
            $candidateData['department'], 
            $candidateData['position'], 
            $candidateId
        );
        if (!$resultStmt->execute()) {
            throw new Exception('Error updating results');
        }
    }

    // Update user's voting status
    $updateUserStmt = $con->prepare("
        UPDATE user 
        SET department = 'Voted' 
        WHERE user_id = ?
    ");
    $updateUserStmt->bind_param("s", $user_id);
    if (!$updateUserStmt->execute()) {
        throw new Exception('Error updating user status');
    }

    // Commit transaction
    $con->commit();
    echo json_encode(['success' => true, 'message' => 'Votes recorded successfully']);

} catch (Exception $e) {
    // Rollback transaction on error
    $con->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

// Close statements and connection
if (isset($checkVoteStmt)) $checkVoteStmt->close();
if (isset($userStmt)) $userStmt->close();
if (isset($voteStmt)) $voteStmt->close();
if (isset($candidateStmt)) $candidateStmt->close();
if (isset($resultStmt)) $resultStmt->close();
if (isset($updateUserStmt)) $updateUserStmt->close();
$con->close();
?> 