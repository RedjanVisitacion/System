<?php
require_once 'connection.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $name = $_POST['full_name'] ?? '';
    $department = $_POST['department'] ?? '';
    $position = $_POST['position'] ?? '';
    $age = $_POST['age'] ?? '';
    $platform = $_POST['info'] ?? '';
    
    // Validate required fields
    if (empty($name) || empty($department) || empty($position) || empty($age) || empty($platform)) {
        echo json_encode([
            'success' => false,
            'message' => 'All fields are required'
        ]);
        exit;
    }
    
    // Handle file upload
    $photo = null;
    if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] === UPLOAD_ERR_OK) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $filename = $_FILES['profile_pic']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if (!in_array($ext, $allowed)) {
            echo json_encode([
                'success' => false,
                'message' => 'Invalid file type. Only JPG, JPEG, PNG, and GIF are allowed.'
            ]);
            exit;
        }
        
        $upload_dir = 'uploads/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $new_filename = uniqid() . '.' . $ext;
        $upload_path = $upload_dir . $new_filename;
        
        if (move_uploaded_file($_FILES['profile_pic']['tmp_name'], $upload_path)) {
            $photo = $upload_path;
        }
    }
    
    if ($action === 'edit') {
        $candidate_id = $_POST['candidate_id'] ?? '';
        if (empty($candidate_id)) {
            echo json_encode([
                'success' => false,
                'message' => 'Candidate ID is required for editing'
            ]);
            exit;
        }
        
        // Update existing candidate
        $sql = "UPDATE elecom_candidate SET name = ?, department = ?, position = ?, age = ?, platform = ?";
        $params = [$name, $department, $position, $age, $platform];
        $types = "sssis";
        
        if ($photo) {
            $sql .= ", photo = ?";
            $params[] = $photo;
            $types .= "s";
        }
        
        $sql .= " WHERE candidate_id = ?";
        $params[] = $candidate_id;
        $types .= "i";
        
        $stmt = $con->prepare($sql);
        $stmt->bind_param($types, ...$params);
        
        if ($stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => 'Candidate updated successfully'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Error updating candidate: ' . $con->error
            ]);
        }
    } else {
        // Add new candidate
        if (empty($photo)) {
            echo json_encode([
                'success' => false,
                'message' => 'Profile picture is required for new candidates'
            ]);
            exit;
        }
        
        $stmt = $con->prepare("INSERT INTO elecom_candidate (name, department, position, age, platform, photo) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssiss", $name, $department, $position, $age, $platform, $photo);
        
        if ($stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => 'Candidate added successfully'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Error adding candidate: ' . $con->error
            ]);
        }
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
}
?> 