<?php
require_once 'check_session.php';
require_once 'connection.php';

// Check if user is an officer
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'officer') {
    // Do nothing or return an empty response for non-officers
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (isset($data['action'])) {
        switch ($data['action']) {
            case 'update_dates':
                if (isset($data['start_date']) && isset($data['end_date']) && isset($data['results_date'])) {
                    // Validate dates
                    $start_date = date('Y-m-d H:i:s', strtotime($data['start_date']));
                    $end_date = date('Y-m-d H:i:s', strtotime($data['end_date']));
                    $results_date = date('Y-m-d H:i:s', strtotime($data['results_date']));
                    
                    // Check if dates are valid
                    if ($start_date >= $end_date) {
                        echo json_encode(['success' => false, 'message' => 'End date must be after start date']);
                        exit;
                    }
                    
                    if ($results_date <= $end_date) {
                        echo json_encode(['success' => false, 'message' => 'Results date must be after end date']);
                        exit;
                    }
                    
                    // Update or insert dates
                    $stmt = $con->prepare("INSERT INTO election_dates (start_date, end_date, results_date) 
                                         VALUES (?, ?, ?) 
                                         ON DUPLICATE KEY UPDATE 
                                         start_date = VALUES(start_date),
                                         end_date = VALUES(end_date),
                                         results_date = VALUES(results_date)");
                    
                    $stmt->bind_param("sss", $start_date, $end_date, $results_date);
                    
                    if ($stmt->execute()) {
                        echo json_encode(['success' => true, 'message' => 'Election dates updated successfully']);
                    } else {
                        echo json_encode(['success' => false, 'message' => 'Failed to update election dates']);
                    }
                    $stmt->close();
                } else {
                    echo json_encode(['success' => false, 'message' => 'Missing required date fields']);
                }
                break;
                
            case 'get_dates':
                $stmt = $con->prepare("SELECT start_date, end_date, results_date FROM election_dates LIMIT 1");
                $stmt->execute();
                $result = $stmt->get_result();
                $dates = $result->fetch_assoc();
                $stmt->close();
                
                if ($dates) {
                    echo json_encode([
                        'success' => true,
                        'start_date' => date('F d, Y h:i A', strtotime($dates['start_date'])),
                        'end_date' => date('F d, Y h:i A', strtotime($dates['end_date'])),
                        'results_date' => date('F d, Y h:i A', strtotime($dates['results_date']))
                    ]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'No election dates set']);
                }
                break;
                
            default:
                echo json_encode(['success' => false, 'message' => 'Invalid action']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'No action specified']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?> 