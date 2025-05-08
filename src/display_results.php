<?php
require_once '../function/connection.php';

// Query to get results with candidate information
$query = "SELECT r.*, c.firstname, c.lastname 
          FROM result r 
          JOIN candidate c ON r.candidate_id = c.candidate_id 
          ORDER BY r.department, r.position, r.votes DESC";

$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Election Results</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .result-card {
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .department-header {
            background-color: #f8f9fa;
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <div class="container mt-4">
        <h2 class="text-center mb-4">Election Results</h2>
        
        <?php
        $current_department = '';
        $current_position = '';
        
        if (mysqli_num_rows($result) > 0) {
            while ($row = mysqli_fetch_assoc($result)) {
                // Display department header if it changes
                if ($current_department != $row['department']) {
                    if ($current_department != '') {
                        echo '</div>'; // Close previous department div
                    }
                    $current_department = $row['department'];
                    echo '<div class="department-section mb-4">';
                    echo '<h3 class="department-header">' . htmlspecialchars($current_department) . '</h3>';
                }
                
                // Display position header if it changes
                if ($current_position != $row['position']) {
                    $current_position = $row['position'];
                    echo '<h4 class="mt-3 mb-2">' . htmlspecialchars($current_position) . '</h4>';
                }
                
                // Display candidate result
                echo '<div class="card result-card">';
                echo '<div class="card-body">';
                echo '<h5 class="card-title">' . htmlspecialchars($row['firstname'] . ' ' . $row['lastname']) . '</h5>';
                echo '<p class="card-text">Votes: ' . number_format($row['votes']) . '</p>';
                echo '<small class="text-muted">Published: ' . date('F j, Y g:i A', strtotime($row['published_at'])) . '</small>';
                echo '</div>';
                echo '</div>';
            }
            echo '</div>'; // Close last department div
        } else {
            echo '<div class="alert alert-info">No results available.</div>';
        }
        ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 