<?php
require_once 'connection.php';

header('Content-Type: application/json');

try {
    // Query to get results with candidate information
    $query = "SELECT r.department, r.position, c.name, c.candidate_id, c.photo,
              SUM(r.votes) as votes, MAX(r.published_at) as published_at
              FROM result r 
              JOIN candidate c ON r.candidate_id = c.candidate_id 
              GROUP BY r.department, r.position, c.candidate_id, c.name, c.photo
              ORDER BY r.department, r.position, votes DESC";

    $result = mysqli_query($con, $query);

    if (!$result) {
        throw new Exception("Query failed: " . mysqli_error($con));
    }

    $results = [];
    while ($row = mysqli_fetch_assoc($result)) {
        // Format the photo path
        $row['photo'] = !empty($row['photo']) ? '../uploads/candidate_photos/' . $row['photo'] : '../img/icon.png';
        $results[] = $row;
    }

    echo json_encode([
        'success' => true,
        'results' => $results
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

mysqli_close($con);
?> 