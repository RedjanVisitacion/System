<?php
session_start();
// Include database connection
include_once 'connection.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$logged_user_id = $_SESSION['user_id'];
$errors = [];
$success = false;

// Use the $con variable from connection.php
$mysqli = $con;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $new_password = trim($_POST['new_password'] ?? '');
    $confirm_password = trim($_POST['confirm_password'] ?? '');
    $profile_picture = null;

    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format.";
    }

    // Validate phone (simple validation)
    if (!empty($phone) && !preg_match('/^\+?[0-9]{10,15}$/', $phone)) {
        $errors[] = "Invalid phone number format.";
    }

    // Validate password if provided
    if (!empty($new_password)) {
        if ($new_password !== $confirm_password) {
            $errors[] = "Passwords do not match.";
        } elseif (strlen($new_password) < 6) {
            $errors[] = "Password must be at least 6 characters long.";
        }
    }

    // Fetch current user data
    $stmt = $mysqli->prepare("SELECT * FROM user_profile WHERE user_id = ?");
    $stmt->bind_param("s", $logged_user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $existingProfile = $result->fetch_assoc();
    $stmt->close();

    // Handle file upload
    if (!empty($_FILES['profile_picture']['name'])) {
        $file = $_FILES['profile_picture'];
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mime, $allowed_types)) {
            $errors[] = "Invalid file type. Allowed types: JPG, PNG, GIF.";
        }

        if ($file['size'] > 2 * 1024 * 1024) {
            $errors[] = "File size must be less than 2MB.";
        }

        if (empty($errors)) {
            $upload_dir = __DIR__ . '/../uploads/profile_pictures/';
            
            // Create directory if it doesn't exist
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            // Generate unique filename
            $filename = uniqid($logged_user_id . '_') . '_' . basename($file['name']);
            $target_path = $upload_dir . $filename;

            if (move_uploaded_file($file['tmp_name'], $target_path)) {
                // Delete old profile picture if exists
                if ($existingProfile && $existingProfile['profile_picture']) {
                    $old_file = $upload_dir . $existingProfile['profile_picture'];
                    if (file_exists($old_file)) {
                        unlink($old_file);
                    }
                }
                $profile_picture = $filename;
            } else {
                $errors[] = "Failed to upload file. Please check directory permissions.";
            }
        }
    }

    // Save profile if no errors
    if (empty($errors)) {
        if ($existingProfile) {
            $sql = "UPDATE user_profile SET email = ?, phone = ?, updated_at = CURRENT_TIMESTAMP()";
            $params = [$email, $phone];

            if ($profile_picture) {
                $sql .= ", profile_picture = ?";
                $params[] = $profile_picture;
            }

            $sql .= " WHERE user_id = ?";
            $params[] = $logged_user_id;

            $stmt = $mysqli->prepare($sql);
            $types = str_repeat('s', count($params));
            $stmt->bind_param($types, ...$params);
            
            if ($stmt->execute()) {
                $success = true;
            } else {
                $errors[] = "Failed to update profile.";
            }
            $stmt->close();
        } else {
            $sql = "INSERT INTO user_profile (user_id, email, phone, profile_picture) VALUES (?, ?, ?, ?)";
            $stmt = $mysqli->prepare($sql);
            $stmt->bind_param("ssss", $logged_user_id, $email, $phone, $profile_picture);
            
            if ($stmt->execute()) {
                $success = true;
            } else {
                $errors[] = "Failed to create profile.";
            }
            $stmt->close();
        }

        // Update password if provided
        if (!empty($new_password) && empty($errors)) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $mysqli->prepare("UPDATE user SET password = ? WHERE user_id = ?");
            $stmt->bind_param("ss", $hashed_password, $logged_user_id);
            
            if (!$stmt->execute()) {
                $errors[] = "Failed to update password.";
                $success = false;
            }
            $stmt->close();
        }
    }
}

// Fetch profile for display
$stmt = $mysqli->prepare("SELECT u.user_id, u.role, u.department, up.email, up.phone, up.profile_picture, up.full_name 
                         FROM user u 
                         LEFT JOIN user_profile up ON u.user_id = up.user_id 
                         WHERE u.user_id = ?");
$stmt->bind_param("s", $logged_user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - Electoral Commission</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .profile-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            padding: 2rem;
            margin-top: 2rem;
        }
        .profile-avatar {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            margin: 0 auto 1.5rem;
            position: relative;
            overflow: hidden;
            background: #f8f9fa;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .profile-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .profile-avatar .upload-overlay {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: rgba(0,0,0,0.6);
            color: white;
            padding: 8px;
            font-size: 0.9rem;
            text-align: center;
            cursor: pointer;
            opacity: 0;
            transition: opacity 0.3s;
        }
        .profile-avatar:hover .upload-overlay {
            opacity: 1;
        }
        .form-control:focus {
            border-color: #0d6efd;
            box-shadow: 0 0 0 0.25rem rgba(13,110,253,.25);
        }
        .btn-back-header {
            background: linear-gradient(90deg,rgb(149, 155, 168) 60%,rgba(104, 105, 107, 0.76) 100%);
            color: #fff !important;
            border-radius: 30px;
            font-size: 1.08rem;
            box-shadow: 0 2px 8px rgba(56, 59, 63, 0.1);
            transition: background 0.2s, box-shadow 0.2s, transform 0.1s;
            border: none;
            outline: none;
            text-decoration: none !important;
            margin-left: -720px;
        }
        .btn-back-header:hover, .btn-back-header:focus {
            background: linear-gradient(90deg,rgba(80, 81, 82, 0.38) 60%,rgb(132, 134, 138) 100%);
            color: #fff !important;
            box-shadow: 0 4px 16px rgba(37,99,235,0.18);
            transform: translateY(-2px) scale(1.03);
            text-decoration: none !important;
        }
    </style>
</head>
<body class="bg-light">
    <nav class="navbar navbar-dark bg-primary shadow-sm sticky-top" style="min-height:60px; z-index: 1050;">
        <div class="container d-flex justify-content-start">
            <a href="dashboard_officer.php" class="btn btn-outline-secondary btn-sm bg-white text-dark border-0 d-flex align-items-center gap-2 px-2 py-1 simple-back-btn" style="margin:-90px; border-radius: 50px;">
                <i class="bi bi-arrow-left fs-6"></i>
                <span class="fw-semibold" style="font-size:1rem;">Back</span>
            </a>
        </div>
    </nav>

    <div class="container py-4">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <div class="profile-card">
                    <div class="text-center mb-4">
                        <div class="profile-avatar">
                            <?php if (!empty($user['profile_picture'])): ?>
                                <img src="<?php echo '../uploads/profile_pictures/' . htmlspecialchars($user['profile_picture']); ?>" 
                                     alt="Profile Picture">
                            <?php else: ?>
                                <i class="bi bi-person-circle" style="font-size: 4rem; color: #6c757d;"></i>
                            <?php endif; ?>
                            <label for="profile-picture-input" class="upload-overlay mb-0">
                                <i class="bi bi-camera-fill"></i> Change Photo
                            </label>
                        </div>
                        <h4 class="mb-1"><?php echo !empty($user['full_name']) ? htmlspecialchars($user['full_name']) : htmlspecialchars($user['user_id']); ?></h4>
                        <p class="text-muted"><?php echo htmlspecialchars($user['role']); ?></p>
                    </div>

                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php foreach ($errors as $error): ?>
                                    <li><?php echo htmlspecialchars($error); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <?php if ($success): ?>
                        <div class="alert alert-success">
                            Profile updated successfully!
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="" enctype="multipart/form-data">
                        <input type="file" id="profile-picture-input" name="profile_picture" accept="image/*" class="d-none">
                        
                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address</label>
                            <input type="email" class="form-control" id="email" name="email" 
                                   value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="phone" class="form-label">Phone Number</label>
                            <input type="tel" class="form-control" id="phone" name="phone" 
                                   value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                        </div>

                        <div class="mb-3">
                            <label for="new_password" class="form-label">New Password</label>
                            <input type="password" class="form-control" id="new_password" name="new_password" 
                                   minlength="6">
                            <div class="form-text">Leave blank to keep current password</div>
                        </div>

                        <div class="mb-4">
                            <label for="confirm_password" class="form-label">Confirm New Password</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">Update Profile</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Profile picture preview
        document.getElementById('profile-picture-input').addEventListener('change', function(e) {
            if (this.files && this.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const avatar = document.querySelector('.profile-avatar');
                    avatar.innerHTML = `
                        <img src="${e.target.result}" alt="Profile Picture">
                        <label for="profile-picture-input" class="upload-overlay mb-0">
                            <i class="bi bi-camera-fill"></i> Change Photo
                        </label>
                    `;
                }
                reader.readAsDataURL(this.files[0]);
            }
        });

        // Password confirmation validation
        const newPassword = document.getElementById('new_password');
        const confirmPassword = document.getElementById('confirm_password');

        function validatePassword() {
            if (confirmPassword.value !== newPassword.value) {
                confirmPassword.setCustomValidity("Passwords don't match");
            } else {
                confirmPassword.setCustomValidity('');
            }
        }

        newPassword.addEventListener('change', validatePassword);
        confirmPassword.addEventListener('change', validatePassword);
    </script>
</body>
</html>
