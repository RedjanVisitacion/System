<?php
require_once 'connection.php';
header('Content-Type: application/json');

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Corrected field name: program_name instead of program
$query = "
    SELECT 
        up.user_id,
        up.full_name,
        up.section_name,
        up.program_name,
        up.gender
    FROM user_profile up
    WHERE up.user_id IN (
        SELECT user_id FROM user WHERE role = 'student'
    )
    ORDER BY up.full_name ASC
";

$result = $con->query($query);

if ($result) {
    $voters = [];

    while ($row = $result->fetch_assoc()) {
        $voters[] = [
            'user_id' => $row['user_id'],
            'full_name' => $row['full_name'],
            'section_name' => $row['section_name'] ?? 'N/A',
            'program_name' => $row['program_name'] ?? 'N/A',
            'gender' => $row['gender'] ?? 'N/A'
        ];
    }

    echo json_encode(['success' => true, 'voters' => $voters]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to fetch voters.', 'error' => $con->error]);
}

$con->close();
?>
