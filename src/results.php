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
$query = "SELECT r.*, c.name 
          FROM result r 
          JOIN candidate c ON r.candidate_id = c.candidate_id 
          ORDER BY r.department, r.position, r.votes DESC";

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
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: #f6fafd;
        }
        .navbar {
            background: #2563eb;
            height: 60px;
        }
        .result-card {
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            border-radius: 12px;
            transition: transform 0.2s;
        }
        .result-card:hover {
            transform: translateY(-2px);
        }
        .department-header {
            background: linear-gradient(135deg, #2563eb 0%, #1e40af 100%);
            color: white;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(37,99,235,0.15);
        }
        .position-header {
            background: #f8fafc;
            padding: 10px 15px;
            margin: 15px 0;
            border-radius: 8px;
            border-left: 4px solid #2563eb;
        }
        .votes-badge {
            background: #e0e7ff;
            color: #2563eb;
            padding: 5px 12px;
            border-radius: 20px;
            font-weight: 600;
        }
        .published-date {
            color: #64748b;
            font-size: 0.9rem;
        }
        .profile-button img {
            border: 2px solid #fff;
        }
        .voted-message {
            background: #dcfce7;
            border: 1px solid #86efac;
            color: #166534;
            padding: 15px;
            border-radius: 12px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .voted-message i {
            font-size: 1.5rem;
        }
        @media (max-width: 768px) {
            .container {
                padding: 10px;
            }
            .department-header {
                margin: 10px 0;
            }
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg position-relative">
        <div class="container-fluid px-4">
            <div class="d-flex align-items-center">
                <img src="../img/icon.png" alt="Electoral Commission Logo" class="me-3" style="width:44px; height:44px; background:#fff; border-radius:50%;">
                <span class="navbar-brand mb-0 h1 text-white">Election Results</span>
            </div>
            <div class="d-flex align-items-center">
           
            <a href="<?php echo $_SESSION['role'] === 'officer' ? 'dashboard_officer.php' : 'dashboard_student.php'; ?>" class="btn btn-outline-light rounded-pill d-flex align-items-center gap-2 px-3 py-1" style="font-weight:500;">
                <i class="bi bi-arrow-left fs-6"></i>
                <span class="fw-semibold" style="font-size:1rem;">Back</span>
            </a>    



                <div class="dropdown">
                    <a href="#" class="btn btn-outline-light rounded-pill d-flex align-items-center" role="button" data-bs-toggle="dropdown">
                        <?php if ($profile_picture): ?>
                            <img src="<?php echo $profile_picture; ?>" alt="Profile Picture" style="width: 32px; height: 32px; border-radius: 50%; object-fit: cover; margin-right: 8px;">
                        <?php else: ?>
                            <i class="bi bi-person-circle me-2"></i>
                        <?php endif; ?>
                        <?php echo htmlspecialchars($user_profile['full_name'] ?? ''); ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="profile.php"><i class="bi bi-person me-2"></i>Profile</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <div class="container py-4">
        <?php if ($has_voted): ?>
            <div class="voted-message">
                <i class="bi bi-check-circle-fill"></i>
                <div>
                    <strong>Thank you for voting!</strong>
                    <p class="mb-0">Your vote has been recorded successfully.</p>
                </div>
            </div>
        <?php endif; ?>

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
                    echo '<h4 class="position-header">' . htmlspecialchars($current_position) . '</h4>';
                }
                
                // Display candidate result
                echo '<div class="card result-card">';
                echo '<div class="card-body">';
                echo '<div class="d-flex justify-content-between align-items-center">';
                echo '<h5 class="card-title mb-0">' . htmlspecialchars($row['name']) . '</h5>';
                echo '<span class="votes-badge">' . number_format($row['votes']) . ' votes</span>';
                echo '</div>';
                echo '<small class="published-date">Published: ' . date('F j, Y g:i A', strtotime($row['published_at'])) . '</small>';
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