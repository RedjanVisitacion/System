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

 
    <!-- Cropper.js CSS -->
    <link  href="https://unpkg.com/cropperjs/dist/cropper.min.css" rel="stylesheet">
    <!-- Cropper.js JS -->
    <script src="https://unpkg.com/cropperjs"></script>


    <style>
        .profile-card {
    background: linear-gradient(135deg, rgba(136, 153, 196, 0.85) 0%, hsla(0, 0.00%, 100.00%, 0.70) 50%, rgba(248,250,252,0.7) 100%),
                url('') center/cover no-repeat;
    border-radius: 24px;
    box-shadow: 0 8px 32px rgba(37,99,235,0.10);
    padding: 2.8rem 2.2rem 2.2rem;
    margin: 2.5rem auto 0;
    width: 100%;
    max-width: 430px;
    position: relative;
}

@media (min-width: 992px) {
    .profile-card {
        max-width: 650px;
        padding: 3rem 2.5rem 2.5rem;
    }
}

@media (max-width: 575.98px) {
    .profile-card {
        padding: 1.5rem 1rem 1.2rem;
        margin-top: 1rem;
        border-radius: 16px;
    }
}

/* Profile Avatar */
.profile-avatar {
    width: 120px;
    height: 120px;
    border-radius: 50%;
    margin: 0 auto 1.2rem;
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

/* Text Styles */
.profile-card h4 {
    font-size: 1.35rem;
    font-weight: 700;
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

/* Form Styling */
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

.form-control,
.form-select {
    border-radius: 12px;
    padding: 0.6rem 1rem;
    font-size: 1.04rem;
    border: 1px solid #e5e7eb;
}

@media (max-width: 575.98px) {
    .form-control,
    .form-select {
        padding: 0.5rem 0.8rem;
        font-size: 0.95rem;
    }
}

/* Password Section */
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
}

@media (max-width: 575.98px) {
    .profile-password-section h6 {
        font-size: 1rem;
        margin-bottom: 0.8rem;
    }
}

/* Primary Button */
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

/* Back Button */
.back-btn-container {
    margin-left: 100px;
    margin-top: 10px;
    position: relative;
}

@media (min-width: 992px) {
    .back-btn-container {
        position: absolute;
        left: 10px;
        top: 10px;
        margin: 0;
    }
}

.simple-back-btn {
    display: inline-flex;
}

@media (min-width: 992px) {
    .simple-back-btn {
        position: absolute;
        left: 10px;
        top: 10px;
        margin-left: 0;
        z-index: 10;
    }
}

/* Navbar Fix */
.navbar {
    display: flex;
    align-items: center;
    justify-content: flex-start;
    position: relative;
    padding-left: 15px;
    margin-left: 0;
    min-height: 0;
}

@media (max-width: 575.98px) {
    .navbar {
        min-height: 45px;
        padding-left: 15px;
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

body.bg-light {
    background: linear-gradient(rgba(30,41,59,0.7), rgba(75, 84, 105, 0.5)),
                url('../img/bg.gif') center/cover no-repeat fixed;
    min-height: 100vh;
}

        
    </style>
</head>
<body class="bg-light">

    <audio src="assets/profileBG.mp3" autoplay hidden></audio>

    <nav class="navbar navbar-dark bg-primary shadow-sm" style="background: linear-gradient(90deg, rgb(26, 57, 119), rgb(72, 74, 80)); height: 60px;">
        <div class="">
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
                        <?php 
                        $profileSrc = (!empty($user['profile_picture']) && file_exists('../uploads/profile_pictures/' . $user['profile_picture']))
                            ? '../uploads/profile_pictures/' . htmlspecialchars($user['profile_picture'])
                            : '../img/icon.png';
                        ?>
                        <img src="<?php echo $profileSrc; ?>" alt="Profile Picture" class="img-fluid rounded-circle" style="cursor: pointer;" data-bs-toggle="modal" data-bs-target="#profilePicModal">

                        <!-- Edit Button -->
                        <button type="button" class="btn btn-light btn-edit-avatar shadow position-absolute" id="editProfilePicBtn" title="Edit Photo" style="bottom: 0; right: 0; transform: translate(20%, 10%); padding: 1.0em; font-size: 0.1rem;">
                            <i class="bi bi-camera-fill" style="font-size: 1.1rem;"></i>
                        </button>
                    </div>

                    <div class="profile-divider my-3"></div>
                    <h4 class="mb-1 mt-3 text-center fw-bold">
                        <?php echo htmlspecialchars($user['full_name'] ?? ''); ?>
                    </h4>
                    <p class="text-muted text-center mb-4">
                        <?php echo htmlspecialchars($user['role'] ?? ''); ?>
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
                    <!-- Hidden File Input for Profile Picture -->
                    <input type="file" id="profile-picture-input" name="profile_picture" accept="image/*" class="d-none">
                    <div class="row g-3">
                        <!-- Full Name Input -->
                        <div class="col-12 col-md-6 profile-form-row">
                            <label for="full_name" class="form-label">Full Name</label>
                            <input type="text" class="form-control" id="full_name" name="full_name" value="<?php echo htmlspecialchars($user['full_name'] ?? ''); ?>" required>
                        </div>
                        
                        <!-- Section Name Input -->
                        <div class="col-12 col-md-6 profile-form-row">
                            <label for="section_name" class="form-label">Section Name</label>
                            <input type="text" class="form-control" id="section_name" name="section_name" value="<?php echo htmlspecialchars($user['section_name'] ?? ''); ?>">
                        </div>
                        
                        <!-- Program Name Input -->
                        <div class="col-12 col-md-6 profile-form-row">
                            <label for="program_name" class="form-label">Program Name</label>
                            <input type="text" class="form-control" id="program_name" name="program_name" value="<?php echo htmlspecialchars($user['program_name'] ?? ''); ?>">
                        </div>
                        
                        <!-- Year Level Select -->
                        <div class="col-12 col-md-6 profile-form-row">
                            <label for="year_level" class="form-label">Year Level</label>
                            <select class="form-select" id="year_level" name="year_level" required>
                                <option value="">Select Year Level</option>
                                <option value="1" <?php if (($user['year_level'] ?? '') == '1') echo 'selected'; ?>>1st Year</option>
                                <option value="2" <?php if (($user['year_level'] ?? '') == '2') echo 'selected'; ?>>2nd Year</option>
                                <option value="3" <?php if (($user['year_level'] ?? '') == '3') echo 'selected'; ?>>3rd Year</option>
                                <option value="4" <?php if (($user['year_level'] ?? '') == '4') echo 'selected'; ?>>4th Year</option>
                            </select>
                        </div>
                        
                        <!-- Gender Select -->
                        <div class="col-12 col-md-6 profile-form-row">
                            <label for="gender" class="form-label">Gender</label>
                            <select class="form-select" id="gender" name="gender" required>
                                <option value="">Select Gender</option>
                                <option value="Male" <?php if (($user['gender'] ?? '') == 'Male') echo 'selected'; ?>>Male</option>
                                <option value="Female" <?php if (($user['gender'] ?? '') == 'Female') echo 'selected'; ?>>Female</option>
                                <option value="Other" <?php if (($user['gender'] ?? '') == 'Other') echo 'selected'; ?>>Other</option>
                            </select>
                        </div>

                        <!-- Email Input -->
                        <div class="col-12 col-md-6 profile-form-row">
                            <label for="email" class="form-label">Email Address</label>
                            <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" required>
                        </div>
                        
                        <!-- Phone Number Input -->
                        <div class="col-12 col-md-6 profile-form-row">
                            <label for="phone" class="form-label">Phone Number</label>
                            <input type="tel" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                        </div>
                    </div>

                    <!-- Password Section -->
                    <div class="profile-password-section mt-4">
                        <h6>Change Password</h6>
                        <div class="mb-3">
                            <label for="new_password" class="form-label">New Password</label>
                            <input type="password" class="form-control" id="new_password" name="new_password" minlength="6">
                            <div class="form-text">Leave blank to keep the current password</div>
                        </div>
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Confirm New Password</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div class="d-grid mt-4">
                        <button type="submit" class="btn btn-primary">Update Profile</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>


<!-- Profile Picture Modal -->
<div class="modal fade" id="profilePicModal" tabindex="-1" aria-labelledby="profilePicModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg">
      <div class="modal-body position-relative p-3">
        <!-- Close Button -->
        <button type="button" class="btn-close position-absolute top-0 end-0 m-2" data-bs-dismiss="modal" aria-label="Close"></button>
        
        <!-- Bordered Container -->
        <div class="border border-secondary rounded p-2 bg-white text-center">
          <img src="<?php echo $profileSrc; ?>" 
               class="img-fluid rounded" 
               style="max-width: 100%; max-height: 80vh; object-fit: contain;" 
               alt="Enlarged Profile Picture">
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Crop Modal -->
<div class="modal fade" id="cropModal" tabindex="-1" aria-labelledby="cropModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="cropModalLabel">Crop Image</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body text-center">
        <img id="cropperImage" src="" style="max-width: 100%; display: block; margin: auto;">
      </div>
      <div class="modal-footer">
        <button type="button" id="cropAndSave" class="btn btn-primary">Crop & Save</button>
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







        document.addEventListener('DOMContentLoaded', function () {
            const editBtn = document.getElementById('editProfilePicBtn');
            const fileInput = document.getElementById('profile-picture-input');
            const avatarContainer = document.querySelector('.profile-avatar');
            let avatar = avatarContainer.querySelector('img');
            let avatarIcon = avatarContainer.querySelector('i.bi-person-circle');
            const deleteModal = new bootstrap.Modal(document.getElementById('deletePhotoModal'));

            // Determine if there's no image or it's using the default
            const isImageMissingOrDefault = !avatar || avatar.src.includes('../img/icon.png') || avatar.src.trim() === '' || avatar.src === window.location.origin + './img/icon.png';

            if (isImageMissingOrDefault) {
                avatarContainer.innerHTML = `<i class="bi bi-person-circle" style="font-size: 80px;"></i>`;
                avatarIcon = avatarContainer.querySelector('i.bi-person-circle'); // Update reference
                avatar = null;
            }

            // Handle edit button click
            if (editBtn && fileInput) {
                editBtn.addEventListener('click', function () {
                    fileInput.click();
                });
            }

            // Handle file input change
            if (fileInput) {
                fileInput.addEventListener('change', function () {
                    if (this.files && this.files[0]) {
                        const reader = new FileReader();
                        reader.onload = function (e) {
                            const imageTag = `<img src='${e.target.result}' alt='Profile Picture' style='width:100%;height:100%;object-fit:cover;border-radius:50%;'>`;

                            avatarContainer.innerHTML = imageTag;
                            avatar = avatarContainer.querySelector('img'); // Update reference
                            avatarIcon = null;
                        };
                        reader.readAsDataURL(this.files[0]);
                    }
                });
            }

            // Optional: Hide file input when clicking outside
            document.addEventListener('click', function (e) {
                if (fileInput && !fileInput.classList.contains('d-none') &&
                    !fileInput.contains(e.target) && e.target !== editBtn) {
                    fileInput.classList.add('d-none');
                }
            });
        });

        let cropper;
        const cropperModal = new bootstrap.Modal(document.getElementById('cropModal'));
        const cropperImage = document.getElementById('cropperImage');
        const cropAndSaveBtn = document.getElementById('cropAndSave');

        fileInput.addEventListener('change', function () {
            if (this.files && this.files[0]) {
                const reader = new FileReader();
                reader.onload = function (e) {
                    cropperImage.src = e.target.result;
                    cropperModal.show();
                };
                reader.readAsDataURL(this.files[0]);
            }
        });

        document.getElementById('cropModal').addEventListener('shown.bs.modal', function () {
            cropper = new Cropper(cropperImage, {
                aspectRatio: 1,
                viewMode: 1,
                movable: false,
                zoomable: false,
                rotatable: false,
                scalable: false,
                cropBoxResizable: true,
                dragMode: 'move'
            });
        });

        document.getElementById('cropModal').addEventListener('hidden.bs.modal', function () {
            cropper.destroy();
            cropper = null;
        });

        cropAndSaveBtn.addEventListener('click', function () {
            const canvas = cropper.getCroppedCanvas({
                width: 300,
                height: 300,
            });

            const dataURL = canvas.toDataURL();
            avatarContainer.innerHTML = `<img src="${dataURL}" alt="Cropped Profile" style="width:100%;height:100%;object-fit:cover;border-radius:50%;">`;
            cropperModal.hide();
        });


    </script>
</body>
</html>
