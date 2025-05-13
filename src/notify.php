<?php
require_once 'check_session.php';
require_once 'connection.php';

// Get user ID from session
$user_id = $_SESSION['user_id'];

// Function to get notifications
function getNotifications($con, $user_id) {
    $notifications = array();
    
    // Check if election_dates table exists
    $check_table = $con->query("SHOW TABLES LIKE 'election_dates'");
    if ($check_table->num_rows > 0) {
        // Get election dates
        $stmt = $con->prepare("SELECT start_date, end_date, results_date FROM election_dates ORDER BY id DESC LIMIT 1");
        if ($stmt) {
            $stmt->execute();
            $result = $stmt->get_result();
            $dates = $result->fetch_assoc();
            
            if ($dates) {
                $now = new DateTime();
                $start_date = new DateTime($dates['start_date']);
                $end_date = new DateTime($dates['end_date']);
                $results_date = new DateTime($dates['results_date']);
                
                // Add election start notification
                if ($now < $start_date) {
                    $notifications[] = array(
                        'type' => 'info',
                        'message' => 'Election will start on ' . $start_date->format('F j, Y g:i A'),
                        'time' => $start_date->format('Y-m-d H:i:s')
                    );
                }
                
                // Add election end notification
                if ($now < $end_date) {
                    $notifications[] = array(
                        'type' => 'warning',
                        'message' => 'Election will end on ' . $end_date->format('F j, Y g:i A'),
                        'time' => $end_date->format('Y-m-d H:i:s')
                    );
                }
                
                // Add results notification
                if ($now < $results_date) {
                    $notifications[] = array(
                        'type' => 'success',
                        'message' => 'Election results will be announced on ' . $results_date->format('F j, Y g:i A'),
                        'time' => $results_date->format('Y-m-d H:i:s')
                    );
                }
            }
            $stmt->close();
        }
    }
    
    // Check if votes table exists
    $check_votes_table = $con->query("SHOW TABLES LIKE 'votes'");
    if ($check_votes_table->num_rows > 0) {
        // Get total votes cast
        $stmt = $con->prepare("SELECT COUNT(*) as total_votes FROM votes");
        if ($stmt) {
            $stmt->execute();
            $result = $stmt->get_result();
            $votes = $result->fetch_assoc();
            
            if ($votes && $votes['total_votes'] > 0) {
                $notifications[] = array(
                    'type' => 'info',
                    'message' => 'Total votes cast: ' . $votes['total_votes'],
                    'time' => date('Y-m-d H:i:s')
                );
            }
            $stmt->close();
        }
    }
    
    // Sort notifications by time
    usort($notifications, function($a, $b) {
        return strtotime($b['time']) - strtotime($a['time']);
    });
    
    return $notifications;
}

// Get notifications
$notifications = getNotifications($con, $user_id);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <style>
        body {
            background: #f6fafd;
            font-family: 'Poppins', sans-serif;
        }
        .notification-card {
            border-radius: 15px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            transition: transform 0.2s;
        }
        .notification-card:hover {
            transform: translateY(-2px);
        }
        .notification-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
        }
        .notification-info {
            background-color: #e3f2fd;
        }
        .notification-warning {
            background-color: #fff3e0;
        }
        .notification-success {
            background-color: #e8f5e9;
        }
        .back-button {
            position: fixed;
            top: 20px;
            left: 20px;
            z-index: 1000;
        }
    </style>
</head>
<body>
    <div class="container py-5">
        <a href="dashboard_officer.php" class="btn btn-primary back-button">
            <i class="bi bi-arrow-left"></i> Back to Dashboard
        </a>
        
        <h2 class="text-center mb-4">Notifications</h2>
        
        <?php if (empty($notifications)): ?>
            <div class="text-center text-muted">
                <i class="bi bi-bell-slash" style="font-size: 3rem;"></i>
                <p class="mt-3">No notifications at the moment</p>
            </div>
        <?php else: ?>
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <?php foreach ($notifications as $notification): ?>
                        <div class="card notification-card mb-3">
                            <div class="card-body d-flex align-items-center">
                                <div class="notification-icon me-3 
                                    <?php 
                                    switch($notification['type']) {
                                        case 'info':
                                            echo 'notification-info';
                                            break;
                                        case 'warning':
                                            echo 'notification-warning';
                                            break;
                                        case 'success':
                                            echo 'notification-success';
                                            break;
                                    }
                                    ?>">
                                    <i class="bi 
                                        <?php 
                                        switch($notification['type']) {
                                            case 'info':
                                                echo 'bi-info-circle';
                                                break;
                                            case 'warning':
                                                echo 'bi-exclamation-triangle';
                                                break;
                                            case 'success':
                                                echo 'bi-check-circle';
                                                break;
                                        }
                                        ?>">
                                    </i>
                                </div>
                                <div class="flex-grow-1">
                                    <p class="mb-1"><?php echo htmlspecialchars($notification['message']); ?></p>
                                    <small class="text-muted">
                                        <?php echo date('F j, Y g:i A', strtotime($notification['time'])); ?>
                                    </small>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
