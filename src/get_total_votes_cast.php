<?php
require_once 'connection.php';
session_start();

try {
    // Get total votes
    $totalVotesQuery = "SELECT COUNT(DISTINCT user_id) as total_votes FROM elecom_vote";
    $totalVotesResult = $con->query($totalVotesQuery);
    $totalVotes = $totalVotesResult->fetch_assoc()['total_votes'];

    // Get list of users who have voted
    $votedUsersQuery = "
        SELECT DISTINCT u.user_id, up.full_name, up.program_name
        FROM elecom_vote v
        JOIN elecom_user u ON v.user_id = u.user_id
        JOIN elecom_user_profile up ON u.user_id = up.user_id
        ORDER BY up.full_name ASC
    ";
    $votedUsersResult = $con->query($votedUsersQuery);
    
    $votedUsers = [];
    while ($row = $votedUsersResult->fetch_assoc()) {
        $votedUsers[] = [
            'full_name' => htmlspecialchars($row['full_name']),
            'program_name' => htmlspecialchars($row['program_name'])
        ];
    }

    echo json_encode([
        'success' => true,
        'total_votes' => $totalVotes,
        'voted_users' => $votedUsers
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching vote data: ' . $e->getMessage()
    ]);
}

$con->close();
?> 