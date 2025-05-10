<?php
require_once 'connection.php';

header('Content-Type: application/json');

try {
    // Query to get results with candidate information, including candidates with no votes
    $query = "SELECT c.department, c.position, c.name, c.candidate_id, c.photo,
              COALESCE(SUM(r.votes), 0) as votes, 
              MAX(r.published_at) as published_at
              FROM candidate c
              LEFT JOIN result r ON c.candidate_id = r.candidate_id 
              WHERE c.department IN ('USG', 'PAFE', 'SITE', 'AFPROTECHS')
              GROUP BY c.department, c.position, c.candidate_id, c.name, c.photo
              ORDER BY FIELD(c.department, 'USG', 'PAFE', 'SITE', 'AFPROTECHS'), 
                       c.position, 
                       votes DESC";

    $result = mysqli_query($con, $query);

    if (!$result) {
        throw new Exception("Query failed: " . mysqli_error($con));
    }

    $results = array();
    while ($row = mysqli_fetch_assoc($result)) {
        // Format the photo path
        $photo = !empty($row['photo']) ? '../uploads/candidate_photos/' . $row['photo'] : '../img/icon.png';
        
        $results[] = array(
            'department' => $row['department'],
            'position' => $row['position'],
            'name' => $row['name'],
            'candidate_id' => $row['candidate_id'],
            'photo' => $photo,
            'votes' => (int)$row['votes'],
            'published_at' => $row['published_at']
        );
    }

    echo json_encode(array(
        'success' => true,
        'results' => $results
    ));

} catch (Exception $e) {
    echo json_encode(array(
        'success' => false,
        'message' => $e->getMessage()
    ));
}

mysqli_close($con);
?> 