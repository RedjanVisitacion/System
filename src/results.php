<?php
require_once 'check_session.php';
require_once 'connection.php';

// Fetch user's profile picture and full name
$user_id = $_SESSION['user_id'];
$stmt = $con->prepare("SELECT profile_picture, full_name FROM user_profile WHERE user_id = ?");
$stmt->bind_param("s", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user_profile = $result->fetch_assoc();
$stmt->close();

// Set profile picture path
$profile_picture = !empty($user_profile['profile_picture']) 
    ? '../uploads/profile_pictures/' . htmlspecialchars($user_profile['profile_picture'])
    : '../img/icon.png';

// Check if user has already voted
$stmt = $con->prepare("SELECT COUNT(*) as vote_count FROM vote WHERE user_id = ? AND vote_status = 'Voted'");
$stmt->bind_param("s", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$vote_count = $result->fetch_assoc()['vote_count'];
$stmt->close();

$has_voted = $vote_count > 0;

// Query to get results with candidate information
$query = "SELECT r.department, r.position, c.name, c.candidate_id, 
          SUM(r.votes) as total_votes, MAX(r.published_at) as published_at
          FROM result r 
          JOIN candidate c ON r.candidate_id = c.candidate_id 
          GROUP BY r.department, r.position, c.candidate_id, c.name
          ORDER BY r.department, r.position, total_votes DESC";

$result = mysqli_query($con, $query);

// Check if query was successful
if (!$result) {
    die("Query failed: " . mysqli_error($con));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="icon" href="../img/icon.png"/>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Election Results</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: #f6fafd;
            color: #1f2937;
        }
        .navbar {
            background: linear-gradient(135deg, #2563eb 0%, #1e40af 100%);
            height: 60px;
            box-shadow: 0 2px 8px rgba(37,99,235,0.15);
        }
        .result-card {
            background: #fff;
            border: none;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
            margin-bottom: 1rem;
            overflow: hidden;
        }
        .result-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .department-header {
            background: linear-gradient(135deg, #2563eb 0%, #1e40af 100%);
            color: white;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(37,99,235,0.15);
        }
        .position-header {
            background: #f8fafc;
            padding: 1rem 1.5rem;
            margin: 1.5rem 0;
            border-radius: 8px;
            border-left: 4px solid #2563eb;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .votes-badge {
            background: #e0e7ff;
            color: #2563eb;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.9rem;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        .votes-badge i {
            font-size: 1rem;
        }
        .published-date {
            color: #64748b;
            font-size: 0.85rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .voted-message {
            background: #dcfce7;
            border: 1px solid #86efac;
            color: #166534;
            padding: 1.25rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            box-shadow: 0 2px 8px rgba(22,101,52,0.1);
        }
        .voted-message i {
            font-size: 2rem;
            color: #16a34a;
        }
        .voted-message-content h5 {
            margin: 0;
            font-weight: 600;
            color: #166534;
        }
        .voted-message-content p {
            margin: 0.25rem 0 0;
            font-size: 0.9rem;
            color: #15803d;
        }
        .candidate-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        .candidate-photo {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #e5e7eb;
        }
        .candidate-details {
            flex: 1;
        }
        .candidate-name {
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 0.25rem;
        }
        .candidate-position {
            font-size: 0.85rem;
            color: #6b7280;
        }
        .progress-container {
            margin-top: 0.75rem;
            background: #f1f5f9;
            border-radius: 8px;
            overflow: hidden;
        }
        .progress {
            height: 8px;
            background: linear-gradient(90deg, #2563eb 0%, #1e40af 100%);
            border-radius: 4px;
        }
        .total-votes {
            background: #f8fafc;
            padding: 0.75rem 1rem;
            border-radius: 8px;
            margin-top: 0.75rem;
            font-weight: 500;
            color: #2563eb;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .total-votes i {
            font-size: 1.1rem;
        }
        @media (max-width: 768px) {
            .container {
                padding: 1rem;
            }
            .department-header {
                padding: 1rem;
                margin: 1rem 0;
            }
            .position-header {
                padding: 0.75rem 1rem;
                margin: 1rem 0;
            }
            .candidate-photo {
                width: 40px;
                height: 40px;
            }
        }

        .backB{
            margin-left: 20px;
        }

        .chart-container {
            position: relative;
            height: 300px;
            margin: 20px 0;
            padding: 15px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }

        .winner-badge {
            position: absolute;
            top: -10px;
            right: -10px;
            background: #fbbf24;
            color: #92400e;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 5px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            z-index: 1;
        }

        .position-section {
            margin-bottom: 2rem;
            position: relative;
        }

        @media (max-width: 768px) {
            .chart-container {
                height: 250px;
                margin: 15px 0;
                padding: 12px;
            }
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    
    <nav class="navbar navbar-dark bg-primary shadow-sm" style="min-height:60px; z-index: 1050;">
        <div class="backB">
            <a href="<?php echo $_SESSION['role'] === 'officer' ? 'dashboard_officer.php' : 'dashboard_student.php'; ?>" class="btn btn-outline-light rounded-pill d-flex align-items-center gap-2 px-3 py-1" style="font-weight:500;">
                <i class="bi bi-arrow-left fs-6"></i>
                <span class="fw-semibold" style="font-size:1rem;">Back</span>
            </a>
        </div>
    </nav>
    

    <div class="container py-4">
        <?php if ($has_voted): ?>
            <div class="voted-message">
                <i class="bi bi-check-circle-fill"></i>
                <div class="voted-message-content">
                    <h5>Thank you for voting!</h5>
                    <p>Your vote has been recorded successfully.</p>
                </div>
            </div>
        <?php endif; ?>

        <?php
        $current_department = '';
        $current_position = '';
        $position_total_votes = 0;
        $position_data = [];
        
        if (mysqli_num_rows($result) > 0) {
            while ($row = mysqli_fetch_assoc($result)) {
                // Display department header if it changes
                if ($current_department != $row['department']) {
                    if ($current_department != '') {
                        // Output the last position's chart
                        if (!empty($position_data)) {
                            outputPositionChart($current_position, $position_data);
                        }
                        echo '</div>'; // Close previous department div
                    }
                    $current_department = $row['department'];
                    echo '<div class="department-section mb-4">';
                    echo '<h3 class="department-header">' . htmlspecialchars($current_department) . '</h3>';
                }
                
                // Display position header if it changes
                if ($current_position != $row['position']) {
                    if ($current_position != '') {
                        // Output the previous position's chart
                        if (!empty($position_data)) {
                            outputPositionChart($current_position, $position_data);
                        }
                    }
                    $current_position = $row['position'];
                    $position_total_votes = 0;
                    $position_data = [];
                    echo '<h4 class="position-header">' . htmlspecialchars($current_position) . '</h4>';
                }
                
                // Add to position total votes
                $position_total_votes += $row['total_votes'];
                
                // Store data for chart
                $position_data[] = [
                    'name' => $row['name'],
                    'votes' => $row['total_votes']
                ];
            }
            
            // Output the last position's chart
            if (!empty($position_data)) {
                outputPositionChart($current_position, $position_data);
            }
            
            echo '</div>'; // Close last department div
        } else {
            echo '<div class="alert alert-info">No results available.</div>';
        }

        function outputPositionChart($position, $data) {
            $labels = array_map(function($item) { return $item['name']; }, $data);
            $votes = array_map(function($item) { return $item['votes']; }, $data);
            $chartId = 'chart_' . md5($position);
            
            // Find the leading candidate
            $maxVotes = max($votes);
            $leadingIndex = array_search($maxVotes, $votes);
            $leadingName = $labels[$leadingIndex];
            
            echo '<div class="position-section">';
            echo '<div class="winner-badge"><i class="bi bi-trophy-fill"></i> Leading: ' . htmlspecialchars($leadingName) . '</div>';
            echo '<div class="chart-container">';
            echo '<canvas id="' . $chartId . '"></canvas>';
            echo '</div>';
            echo '</div>';
            
            echo '<script>
                new Chart(document.getElementById("' . $chartId . '"), {
                    type: "bar",
                    data: {
                        labels: ' . json_encode($labels) . ',
                        datasets: [{
                            label: "Votes",
                            data: ' . json_encode($votes) . ',
                            backgroundColor: function(context) {
                                const index = context.dataIndex;
                                const value = context.dataset.data[index];
                                const max = Math.max(...context.dataset.data);
                                return value === max ? 
                                    "rgba(234, 179, 8, 0.85)" : // Gold color for winner
                                    "rgba(37, 99, 235, 0.85)";
                            },
                            borderColor: function(context) {
                                const index = context.dataIndex;
                                const value = context.dataset.data[index];
                                const max = Math.max(...context.dataset.data);
                                return value === max ? 
                                    "rgb(234, 179, 8)" : // Gold border for winner
                                    "rgb(37, 99, 235)";
                            },
                            borderWidth: 2,
                            borderRadius: 6,
                            barThickness: 30,
                            maxBarThickness: 40
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false
                            },
                            title: {
                                display: true,
                                text: "Vote Distribution",
                                font: {
                                    size: 16,
                                    family: "Poppins",
                                    weight: "600"
                                },
                                padding: {
                                    top: 5,
                                    bottom: 15
                                }
                            },
                            tooltip: {
                                backgroundColor: "rgba(0, 0, 0, 0.8)",
                                padding: 10,
                                titleFont: {
                                    size: 13,
                                    family: "Poppins",
                                    weight: "600"
                                },
                                bodyFont: {
                                    size: 12,
                                    family: "Poppins"
                                },
                                cornerRadius: 6,
                                displayColors: false
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    precision: 0,
                                    font: {
                                        size: 11,
                                        family: "Poppins"
                                    },
                                    padding: 8
                                },
                                grid: {
                                    color: "rgba(0, 0, 0, 0.05)",
                                    drawBorder: false
                                }
                            },
                            x: {
                                ticks: {
                                    font: {
                                        size: 11,
                                        family: "Poppins"
                                    },
                                    padding: 8,
                                    maxRotation: 45,
                                    minRotation: 45
                                },
                                grid: {
                                    display: false
                                }
                            }
                        },
                        animation: {
                            duration: 1000,
                            easing: "easeOutQuart"
                        }
                    }
                });
            </script>';
        }
        ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 