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
            background: white;
            border-radius: 15px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            padding: 2.5rem 2rem;
            margin-top: 2rem;
            max-width: 500px;
            margin-left: auto;
            margin-right: auto;
        }
        .profile-avatar {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            margin: 0 auto 1.5rem;
            position: relative;
            overflow: visible;
            background: #f8f9fa;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        @media (max-width: 575.98px) {
            .profile-card {
                padding: 1.2rem 0.5rem;
                margin-top: 1rem;
                max-width: 98vw;
            }
            .profile-avatar {
                width: 100px;
                height: 100px;
                margin-bottom: 1rem;
            }
            .btn-edit-avatar {
                bottom: 2px;
                right: 2px;
                font-size: 0.95rem;
                padding: 0.3rem 0.4rem;
            }
        }
        .profile-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 50%;
            display: block;
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
        .btn-edit-avatar {
            position: absolute;
            bottom: 10px;
            right: 10px;
            border-radius: 50%;
            padding: 0.5rem 0.6rem;
            font-size: 1.1rem;
            z-index: 2;
            background: #fff;
            color: #2563eb;
            border: 1px solid #e0e7ef;
            transition: background 0.2s, color 0.2s, box-shadow 0.2s;
        }
        .btn-edit-avatar:hover, .btn-edit-avatar:focus {
            background: #2563eb;
            color: #fff;
            box-shadow: 0 4px 16px rgba(37,99,235,0.18);
        }
        #editPhotoOptions.dropdown-menu {
            display: block;
            opacity: 1;
            pointer-events: auto;
            min-width: 170px;
            border-radius: 12px;
            margin-top: 16px;
            left: 50%;
            transform: translateX(-50%);
            box-shadow: 0 8px 32px rgba(37,99,235,0.12);
            background: #fff;
            border: 1px solid #e5e7eb;
            z-index: 1055;
            position: absolute;
            top: 100%;
        }
        #editPhotoOptions.d-none {
            display: none !important;
        }
        #editPhotoOptions .dropdown-item {
            border-radius: 8px;
            transition: background 0.15s, color 0.15s;
            font-size: 1rem;
            padding: 0.5rem 1rem;
        }
        #editPhotoOptions .dropdown-item:hover, #editPhotoOptions .dropdown-item:focus {
            background: #f1f5f9;
            color: #2563eb;
        }
        #editPhotoOptions .dropdown-item.text-danger:hover {
            background: #fee2e2;
            color: #dc2626;
        }
        .simple-back-btn {
            margin-right: 10px;
        }
    </style>
</head>
<body class="bg-light">
    <nav class="navbar navbar-dark bg-primary shadow-sm sticky-top" style="min-height:60px; z-index: 1050;">
        <div class="container d-flex justify-content-start">
            <a href="dashboard_officer.php" class="btn btn-outline-secondary btn-sm bg-white text-dark border-0 d-flex align-items-center gap-2 px-2 py-1 simple-back-btn" style=" border-radius: 50px;">
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
                        <h4 class="mb-1 mt-3"><?php echo htmlspecialchars($user['full_name'] ?? ''); ?></h4>
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
                            <label for="full_name" class="form-label">Full Name</label>
                            <input type="text" class="form-control" id="full_name" name="full_name" value="<?php echo htmlspecialchars($user['full_name'] ?? ''); ?>">
                        </div>
                        <div class="mb-3">
                            <label for="section_name" class="form-label">Section Name</label>
                            <input type="text" class="form-control" id="section_name" name="section_name" value="<?php echo htmlspecialchars($user['section_name'] ?? ''); ?>">
                        </div>
                        <div class="mb-3">
                            <label for="program_name" class="form-label">Program Name</label>
                            <input type="text" class="form-control" id="program_name" name="program_name" value="<?php echo htmlspecialchars($user['program_name'] ?? ''); ?>">
                        </div>
                        <div class="mb-3">
                            <label for="year_level" class="form-label">Year Level</label>
                            <select class="form-select" id="year_level" name="year_level">
                                <option value="">Select Year Level</option>
                                <option value="1" <?php if (($user['year_level'] ?? '') == '1') echo 'selected'; ?>>1st Year</option>
                                <option value="2" <?php if (($user['year_level'] ?? '') == '2') echo 'selected'; ?>>2nd Year</option>
                                <option value="3" <?php if (($user['year_level'] ?? '') == '3') echo 'selected'; ?>>3rd Year</option>
                                <option value="4" <?php if (($user['year_level'] ?? '') == '4') echo 'selected'; ?>>4th Year</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="gender" class="form-label">Gender</label>
                            <select class="form-select" id="gender" name="gender">
                                <option value="">Select Gender</option>
                                <option value="Male" <?php if (($user['gender'] ?? '') == 'Male') echo 'selected'; ?>>Male</option>
                                <option value="Female" <?php if (($user['gender'] ?? '') == 'Female') echo 'selected'; ?>>Female</option>
                                <option value="Other" <?php if (($user['gender'] ?? '') == 'Other') echo 'selected'; ?>>Other</option>
                            </select>
                        </div>

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
