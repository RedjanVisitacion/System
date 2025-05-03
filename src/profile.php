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
    $full_name = trim($_POST['full_name'] ?? '');
    $section_name = trim($_POST['section_name'] ?? '');
    $program_name = trim($_POST['program_name'] ?? '');
    $year_level = trim($_POST['year_level'] ?? '');
    $gender = trim($_POST['gender'] ?? '');

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
            $sql = "UPDATE user_profile SET full_name = ?, section_name = ?, program_name = ?, year_level = ?, gender = ?, email = ?, phone = ?, updated_at = CURRENT_TIMESTAMP()";
            $params = [$full_name, $section_name, $program_name, $year_level, $gender, $email, $phone];

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
            $sql = "INSERT INTO user_profile (user_id, full_name, section_name, program_name, year_level, gender, email, phone, profile_picture) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $mysqli->prepare($sql);
            $stmt->bind_param("sssssssss", $logged_user_id, $full_name, $section_name, $program_name, $year_level, $gender, $email, $phone, $profile_picture);
            
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

    // Delete photo if requested
    if (
        isset($_POST['delete_photo']) && $_POST['delete_photo'] == '1'
    ) {
        // Only handle photo deletion
        $stmt = $mysqli->prepare("SELECT profile_picture FROM user_profile WHERE user_id = ?");
        $stmt->bind_param("s", $logged_user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $profile = $result->fetch_assoc();
        $stmt->close();
        if ($profile && !empty($profile['profile_picture'])) {
            $file = __DIR__ . '/../uploads/profile_pictures/' . $profile['profile_picture'];
            if (file_exists($file)) {
                unlink($file);
            }
            $stmt = $mysqli->prepare("UPDATE user_profile SET profile_picture = NULL WHERE user_id = ?");
            $stmt->bind_param("s", $logged_user_id);
            $stmt->execute();
            $stmt->close();
        }
        header("Location: profile.php");
        exit();
    }
}

// Fetch profile for display
$stmt = $mysqli->prepare("SELECT u.user_id, u.role, u.department, up.email, up.phone, up.profile_picture, up.full_name, up.section_name, up.program_name, up.year_level, up.gender 
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
    <link rel="icon" href="../img/icon.png"/>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .profile-card {
            background: linear-gradient(135deg, #f8fafc 60%, #e0e7ff 100%);
            border-radius: 24px;
            box-shadow: 0 8px 32px rgba(37,99,235,0.10);
            padding: 2.8rem 2.2rem 2.2rem 2.2rem;
            margin-top: 2.5rem;
            width: 100%;
            max-width: 430px;
            margin-left: auto;
            margin-right: auto;
        }
        @media (min-width: 992px) {
            .profile-card {
                max-width: 650px;
                padding: 3rem 2.5rem 2.5rem 2.5rem;
            }
        }
        @media (max-width: 575.98px) {
            .profile-card {
                padding: 1.5rem 1rem 1.2rem 1rem;
                margin-top: 1rem;
                border-radius: 16px;
            }
        }
        .profile-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            margin: 0 auto 1.2rem;
            position: relative;
            background: linear-gradient(135deg, #2563eb 40%, #60a5fa 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 24px rgba(37,99,235,0.10);
            border: 4px solid #fff;
        }
        @media (max-width: 575.98px) {
            .profile-avatar {
                width: 100px;
                height: 100px;
                margin-bottom: 1rem;
            }
        }
        .profile-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 50%;
            border: 3px solid #2563eb;
            background: #fff;
        }
        .btn-edit-avatar {
            position: absolute;
            bottom: -18px;
            left: 50%;
            transform: translateX(-50%);
            border-radius: 50%;
            padding: 0.6rem 0.7rem;
            font-size: 1.2rem;
            z-index: 2;
            background: #2563eb;
            color: #fff;
            border: none;
            box-shadow: 0 2px 8px rgba(37,99,235,0.10);
            transition: background 0.2s, color 0.2s, box-shadow 0.2s;
        }
        @media (max-width: 575.98px) {
            .btn-edit-avatar {
                bottom: -12px;
                padding: 0.4rem 0.5rem;
                font-size: 1rem;
            }
        }
        .profile-divider {
            width: 60px;
            height: 3px;
            background: #2563eb;
            border-radius: 2px;
            margin: 2.2rem auto;
            opacity: 0.18;
        }
        @media (max-width: 575.98px) {
            .profile-divider {
                margin: 1.5rem auto;
            }
        }
        .profile-card h4 {
            font-size: 1.35rem;
            font-weight: 700;
            letter-spacing: 0.5px;
            margin-bottom: 0.2rem;
        }
        @media (max-width: 575.98px) {
            .profile-card h4 {
                font-size: 1.2rem;
            }
        }
        .profile-card p.text-muted {
            font-size: 1.08rem;
            margin-bottom: 0.5rem;
        }
        @media (max-width: 575.98px) {
            .profile-card p.text-muted {
                font-size: 1rem;
            }
        }
        .profile-form-row {
            margin-bottom: 1.1rem;
        }
        @media (max-width: 575.98px) {
            .profile-form-row {
                margin-bottom: 0.8rem;
            }
        }
        .form-label {
            font-weight: 500;
            margin-bottom: 0.3rem;
            font-size: 0.95rem;
        }
        .form-control, .form-select {
            border-radius: 12px;
            padding: 0.6rem 1rem;
            font-size: 1.04rem;
            box-shadow: none;
            border: 1px solid #e5e7eb;
        }
        @media (max-width: 575.98px) {
            .form-control, .form-select {
                padding: 0.5rem 0.8rem;
                font-size: 0.95rem;
            }
        }
        .profile-password-section {
            margin-top: 2.2rem;
            padding-top: 1.2rem;
            border-top: 1.5px solid #e5e7eb;
        }
        @media (max-width: 575.98px) {
            .profile-password-section {
                margin-top: 1.5rem;
                padding-top: 1rem;
            }
        }
        .profile-password-section h6 {
            font-size: 1.08rem;
            font-weight: 600;
            color: #2563eb;
            margin-bottom: 1.1rem;
            letter-spacing: 0.5px;
        }
        @media (max-width: 575.98px) {
            .profile-password-section h6 {
                font-size: 1rem;
                margin-bottom: 0.8rem;
            }
        }
        .btn-primary {
            background: linear-gradient(90deg, #2563eb 60%, #1e40af 100%);
            border: none;
            border-radius: 14px;
            font-weight: 600;
            font-size: 1.1rem;
            padding: 0.7rem 0;
            margin-top: 0.7rem;
            box-shadow: 0 2px 8px rgba(37,99,235,0.08);
            transition: background 0.2s, box-shadow 0.2s;
        }
        @media (max-width: 575.98px) {
            .btn-primary {
                font-size: 1rem;
                padding: 0.6rem 0;
                margin-top: 0.5rem;
            }
        }
        .alert {
            border-radius: 12px;
            padding: 1rem;
            margin-bottom: 1.5rem;
        }
        @media (max-width: 575.98px) {
            .alert {
                padding: 0.8rem;
                margin-bottom: 1rem;
                font-size: 0.95rem;
            }
        }
        .form-text {
            font-size: 0.85rem;
            color: #6b7280;
        }
        @media (max-width: 575.98px) {
            .form-text {
                font-size: 0.8rem;
            }
        }
        .row.g-3 {
            margin: 0 -0.5rem;
        }
        .row.g-3 > * {
            padding: 0 0.5rem;
        }
        @media (max-width: 575.98px) {
            .row.g-3 {
                margin: 0 -0.3rem;
            }
            .row.g-3 > * {
                padding: 0 0.3rem;
            }
        }
        .simple-back-btn {
            margin-left: 10px;
            position: static;
            display: inline-flex;
        }
        @media (min-width: 992px) {
            .simple-back-btn {
                position: absolute;
                left: 10px;
                top: 10px;
                margin-left: 0;
                margin-bottom: 0;
                z-index: 10;
            }
            .profile-card {
                position: relative;
            }
        }
        .navbar {
            position: relative;
            min-height: 60px;
        }
        .back-btn-container {
            position: relative;
            margin: 10px;
        }
        @media (max-width: 575.98px) {
            .navbar {
                min-height: 45px;
                padding: 0;
            }
            .back-btn-container {
                margin: 5px;
            }
            .back-btn-container .btn {
                padding: 0.3rem 0.8rem;
                font-size: 0.9rem;
            }
            .back-btn-container .btn i {
                font-size: 0.9rem;
            }
        }
        @media (min-width: 992px) {
            .back-btn-container {
                position: absolute;
                left: 10px;
                top: 10px;
                margin: 0;
            }
            .navbar {
                padding-left: 60px;
            }
        }
    </style>
</head>
<body class="bg-light">
    <nav class="navbar navbar-dark bg-primary shadow-sm" style="min-height:60px; z-index: 1050;">
        <div class="container d-flex justify-content-start">
            <a href="<?php echo $_SESSION['role'] === 'officer' ? 'dashboard_officer.php' : 'dashboard_student.php'; ?>" class="btn btn-outline-light rounded-pill d-flex align-items-center gap-2 px-3 py-1" style="font-weight:500;">
                <i class="bi bi-arrow-left fs-6"></i>
                <span class="fw-semibold" style="font-size:1rem;">Back</span>
            </a>
        </div>
    </nav>

    <div class="container py-4">
        <div class="row justify-content-center">
            <div class="col-12 col-md-10 col-lg-8">
                <div class="profile-card">
                    <div class="text-center mb-4">
                        <div class="profile-avatar position-relative mx-auto">
                            <?php if (!empty($user['profile_picture'])): ?>
                                <img src="<?php echo '../uploads/profile_pictures/' . htmlspecialchars($user['profile_picture']); ?>" alt="Profile Picture">
                            <?php else: ?>
                                <i class="bi bi-person-circle" style="font-size: 4rem; color: #6c757d;"></i>
                            <?php endif; ?>
                            <button type="button" class="btn btn-light btn-edit-avatar shadow" id="editProfilePicBtn" title="Edit Photo">
                                <i class="bi bi-pencil"></i>
                            </button>
                        </div>
                        <div class="profile-divider"></div>
                        <h4 class="mb-1 mt-3 text-center fw-bold">
                            <?php echo htmlspecialchars($user['full_name'] ?? ''); ?>
                        </h4>
                        <p class="text-muted text-center mb-4">
                            <?php echo htmlspecialchars($user['role']); ?>
                        </p>
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
                        <div class="row g-3">
                            <div class="col-12 col-md-6 profile-form-row">
                                <label for="full_name" class="form-label">Full Name</label>
                                <input type="text" class="form-control" id="full_name" name="full_name" value="<?php echo htmlspecialchars($user['full_name'] ?? ''); ?>">
                            </div>
                            <div class="col-12 col-md-6 profile-form-row">
                                <label for="section_name" class="form-label">Section Name</label>
                                <input type="text" class="form-control" id="section_name" name="section_name" value="<?php echo htmlspecialchars($user['section_name'] ?? ''); ?>">
                            </div>
                            <div class="col-12 col-md-6 profile-form-row">
                                <label for="program_name" class="form-label">Program Name</label>
                                <input type="text" class="form-control" id="program_name" name="program_name" value="<?php echo htmlspecialchars($user['program_name'] ?? ''); ?>">
                            </div>
                            <div class="col-12 col-md-6 profile-form-row">
                                <label for="year_level" class="form-label">Year Level</label>
                                <select class="form-select" id="year_level" name="year_level">
                                    <option value="">Select Year Level</option>
                                    <option value="1" <?php if (($user['year_level'] ?? '') == '1') echo 'selected'; ?>>1st Year</option>
                                    <option value="2" <?php if (($user['year_level'] ?? '') == '2') echo 'selected'; ?>>2nd Year</option>
                                    <option value="3" <?php if (($user['year_level'] ?? '') == '3') echo 'selected'; ?>>3rd Year</option>
                                    <option value="4" <?php if (($user['year_level'] ?? '') == '4') echo 'selected'; ?>>4th Year</option>
                                </select>
                            </div>
                            <div class="col-12 col-md-6 profile-form-row">
                                <label for="gender" class="form-label">Gender</label>
                                <select class="form-select" id="gender" name="gender">
                                    <option value="">Select Gender</option>
                                    <option value="Male" <?php if (($user['gender'] ?? '') == 'Male') echo 'selected'; ?>>Male</option>
                                    <option value="Female" <?php if (($user['gender'] ?? '') == 'Female') echo 'selected'; ?>>Female</option>
                                    <option value="Other" <?php if (($user['gender'] ?? '') == 'Other') echo 'selected'; ?>>Other</option>
                                </select>
                            </div>
                            <div class="col-12 col-md-6 profile-form-row">
                            <label for="email" class="form-label">Email Address</label>
                                <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" required>
                        </div>
                            <div class="col-12 col-md-6 profile-form-row">
                            <label for="phone" class="form-label">Phone Number</label>
                                <input type="tel" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                            </div>
                        </div>
                        <div class="profile-password-section">
                            <h6>Change Password</h6>
                        <div class="mb-3">
                            <label for="new_password" class="form-label">New Password</label>
                                <input type="password" class="form-control" id="new_password" name="new_password" minlength="6">
                            <div class="form-text">Leave blank to keep current password</div>
                        </div>
                            <div class="mb-3">
                            <label for="confirm_password" class="form-label">Confirm New Password</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                            </div>
                        </div>
                        <div class="d-grid mt-4">
                            <button type="submit" class="btn btn-primary">Update Profile</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Photo Modal -->
    <div class="modal fade" id="deletePhotoModal" tabindex="-1" aria-labelledby="deletePhotoModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deletePhotoModalLabel">Delete Profile Photo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Are you sure you want to delete your profile photo?
                </div>
                <div class="modal-footer">
                    <form method="POST" action="" class="m-0">
                        <input type="hidden" name="delete_photo" value="1">
                        <button type="submit" class="btn btn-danger">Delete</button>
                    </form>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const editBtn = document.getElementById('editProfilePicBtn');
            const fileInput = document.getElementById('profile-picture-input');
            const avatar = document.querySelector('.profile-avatar img');
            const avatarIcon = document.querySelector('.profile-avatar i.bi-person-circle');
            const deleteModal = new bootstrap.Modal(document.getElementById('deletePhotoModal'));

            if (editBtn) {
                editBtn.addEventListener('click', function(e) {
                    fileInput.click();
                });
            }
            if (fileInput) {
                fileInput.addEventListener('change', function(e) {
            if (this.files && this.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                            if (avatar) {
                                avatar.src = e.target.result;
                            } else if (avatarIcon) {
                                // Replace icon with image if no previous image
                                avatarIcon.outerHTML = `<img src='${e.target.result}' alt='Profile Picture' style='width:100%;height:100%;object-fit:cover;border-radius:50%;'>`;
                            }
                        };
                reader.readAsDataURL(this.files[0]);
            }
        });
            }
            // Hide options when clicking outside
            document.addEventListener('click', function(e) {
                if (fileInput && !fileInput.classList.contains('d-none') && !fileInput.contains(e.target) && e.target !== editBtn) {
                    fileInput.classList.add('d-none');
                }
            });
        });
    </script>
</body>
</html>
