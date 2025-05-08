<?php
require_once 'connection.php';

// Check if department parameter is set
if (!isset($_GET['department'])) {
    echo json_encode([]);
    exit;
}

$department = mysqli_real_escape_string($con, $_GET['department']);

// Query to get positions for the selected department
$query = "SELECT DISTINCT position FROM result WHERE department = ? ORDER BY position";
$stmt = $con->prepare($query);
$stmt->bind_param("s", $department);
$stmt->execute();
$result = $stmt->get_result();

$positions = [];
while ($row = mysqli_fetch_assoc($result)) {
    $positions[] = $row['position'];
}

// Return positions as JSON
header('Content-Type: application/json');
echo json_encode($positions);
?> 