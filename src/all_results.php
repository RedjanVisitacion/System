<?php
require_once 'check_session.php';
require_once 'connection.php';

// Check if user is an officer
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'officer') {
    header('Location: dashboard_student.php');
    exit();
}

// Fetch user's profile picture and full name
$user_id = $_SESSION['user_id'];
$stmt = $con->prepare("SELECT profile_picture, full_name FROM elecom_user_profile WHERE user_id = ?");
$stmt->bind_param("s", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user_profile = $result->fetch_assoc();
$stmt->close();

// Set profile picture path
$profile_picture = !empty($user_profile['profile_picture']) 
    ? '../uploads/profile_pictures/' . htmlspecialchars($user_profile['profile_picture'])
    : '../img/icon.png';

// Get all departments
$departments_query = "SELECT DISTINCT department FROM elecom_result ORDER BY department";
$departments_result = mysqli_query($con, $departments_query);
$departments = [];
while ($row = mysqli_fetch_assoc($departments_result)) {
    $departments[] = $row['department'];
}

// Get filter parameters
$selected_department = isset($_GET['department']) ? $_GET['department'] : '';
$selected_position = isset($_GET['position']) ? $_GET['position'] : '';

// Build the query
$query = "SELECT r.*, c.name 
          FROM elecom_result r 
          JOIN elecom_candidate c ON r.candidate_id = c.candidate_id";

$where_conditions = [];
if ($selected_department) {
    $where_conditions[] = "r.department = '" . mysqli_real_escape_string($con, $selected_department) . "'";
}
if ($selected_position) {
    $where_conditions[] = "r.position = '" . mysqli_real_escape_string($con, $selected_position) . "'";
}

if (!empty($where_conditions)) {
    $query .= " WHERE " . implode(" AND ", $where_conditions);
}

$query .= " ORDER BY r.department, r.position, r.votes DESC";

$result = mysqli_query($con, $query);

// Check if query was successful
if (!$result) {
    die("Query failed: " . mysqli_error($con));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Election Results</title>
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
        .filter-section {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            margin-bottom: 20px;
        }
        .filter-section select {
            border-radius: 8px;
            border: 1px solid #e2e8f0;
            padding: 8px 12px;
        }
        .filter-section .btn {
            border-radius: 8px;
            padding: 8px 20px;
        }
        .total-votes {
            background: #f8fafc;
            padding: 10px 15px;
            border-radius: 8px;
            margin-top: 10px;
            font-weight: 500;
            color: #2563eb;
        }
        @media (max-width: 768px) {
            .container {
                padding: 10px;
            }
            .department-header {
                margin: 10px 0;
            }
            .filter-section {
                padding: 15px;
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
                <span class="navbar-brand mb-0 h1 text-white">All Election Results</span>
            </div>
            <div class="d-flex align-items-center">
                <a href="dashboard_officer.php" class="btn btn-outline-light me-2">
                    <i class="bi bi-arrow-left"></i> Back to Officer Dashboard
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
        <!-- Filter Section -->
        <div class="filter-section">
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <label for="department" class="form-label">Department</label>
                    <select class="form-select" id="department" name="department">
                        <option value="">All Departments</option>
                        <?php foreach ($departments as $dept): ?>
                            <option value="<?php echo htmlspecialchars($dept); ?>" <?php echo $selected_department === $dept ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($dept); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="position" class="form-label">Position</label>
                    <select class="form-select" id="position" name="position">
                        <option value="">All Positions</option>
                        <?php
                        $positions_query = "SELECT DISTINCT position FROM result ORDER BY position";
                        $positions_result = mysqli_query($con, $positions_query);
                        while ($row = mysqli_fetch_assoc($positions_result)) {
                            $selected = $selected_position === $row['position'] ? 'selected' : '';
                            echo "<option value='" . htmlspecialchars($row['position']) . "' $selected>" . 
                                 htmlspecialchars($row['position']) . "</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary me-2">Apply Filters</button>
                    <a href="all_results.php" class="btn btn-outline-secondary">Reset</a>
                </div>
            </form>
        </div>

        <?php
        $current_department = '';
        $current_position = '';
        $total_votes = 0;
        
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
                    if ($current_position != '') {
                        echo '<div class="total-votes">Total Votes: ' . number_format($total_votes) . '</div>';
                    }
                    $current_position = $row['position'];
                    $total_votes = 0;
                    echo '<h4 class="position-header">' . htmlspecialchars($current_position) . '</h4>';
                }
                
                // Add to total votes
                $total_votes += $row['votes'];
                
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
            
            // Display total votes for the last position
            if ($current_position != '') {
                echo '<div class="total-votes">Total Votes: ' . number_format($total_votes) . '</div>';
            }
            
            echo '</div>'; // Close last department div
        } else {
            echo '<div class="alert alert-info">No results available.</div>';
        }
        ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Add dynamic position filtering based on selected department
        document.getElementById('department').addEventListener('change', function() {
            const department = this.value;
            const positionSelect = document.getElementById('position');
            
            // Clear current positions
            positionSelect.innerHTML = '<option value="">All Positions</option>';
            
            if (department) {
                // Fetch positions for selected department
                fetch(`get_positions.php?department=${encodeURIComponent(department)}`)
                    .then(response => response.json())
                    .then(positions => {
                        positions.forEach(position => {
                            const option = document.createElement('option');
                            option.value = position;
                            option.textContent = position;
                            positionSelect.appendChild(option);
                        });
                    })
                    .catch(error => console.error('Error fetching positions:', error));
            }
        });
    </script>
</body>
</html> 