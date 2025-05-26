<?php
require_once 'connection.php';

$error = '';
$success = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = trim($_POST['user_id']);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);
    $role = trim($_POST['role']);
    $department = trim($_POST['department']);
    
    // Profile information
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $full_name = trim($_POST['full_name']);
    $section_name = trim($_POST['section_name']);
    $program_name = trim($_POST['program_name']);
    $year_level = trim($_POST['year_level']);
    $gender = trim($_POST['gender']);

    // Validate input
    if (empty($user_id) || empty($password) || empty($confirm_password) || empty($role)) {
        $error = "Please fill in all required fields";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match";
    } elseif (strlen($password) < 8) {
        $error = "Password must be at least 8 characters long";
    } else {
        // Check if user_id already exists
        $check_query = "SELECT * FROM elecom_user WHERE user_id = ?";
        $stmt = $con->prepare($check_query);
        $stmt->bind_param("s", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $error = "User ID already exists";
        } else {
            // Start transaction
            $con->begin_transaction();

            try {
                // Hash the password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                // Insert into elecom_user
                $insert_user_query = "INSERT INTO elecom_user (user_id, password, role, department) VALUES (?, ?, ?, ?)";
                $stmt = $con->prepare($insert_user_query);
                $stmt->bind_param("ssss", $user_id, $hashed_password, $role, $department);
                $stmt->execute();

                // Insert into elecom_user_profile
                $insert_profile_query = "INSERT INTO elecom_user_profile (user_id, email, phone, full_name, section_name, program_name, year_level, gender) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $con->prepare($insert_profile_query);
                $stmt->bind_param("ssssssis", $user_id, $email, $phone, $full_name, $section_name, $program_name, $year_level, $gender);
                $stmt->execute();

                // Commit transaction
                $con->commit();
                $success = "Registration successful!";
            } catch (Exception $e) {
                // Rollback transaction on error
                $con->rollback();
                $error = "Registration failed: " . $e->getMessage();
            }
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Registration</title>
    <link rel="icon" href="../img/icon.png"/>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">


    <style>
   
    body {
      min-height: 100vh;
      width: 100vw;
      display: flex;
      align-items: center;
      justify-content: center;
      background: linear-gradient(135deg, rgba(57, 66, 77, 0.5) 0%, rgba(6, 73, 117, 0.9) 100%), url('../img/votE.jpg');
      background-size: cover;
      background-position: center;
      background-repeat: no-repeat;
      background-attachment: fixed;
      
    }

    .card {
      border-radius: 28px;
      box-shadow: 0 8px 40px rgba(60,60,100,0.13), 0 2px 8px rgba(37,99,235,0.08);
      overflow: hidden;
      background: rgba(255,255,255,0.15);
      background: linear-gradient(rgba(210, 213, 218, 0.7), rgba(230, 234, 243, 0.9)),
                url('../img/bg.gif') center/cover no-repeat fixed;
    

      backdrop-filter: blur(8px);
    }

    .highlight {
      color: #2563eb;
      background: linear-gradient(90deg,rgb(50, 78, 201) 0%,rgb(52, 66, 104) 50%, #1e40af 100%);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
      text-fill-color: transparent;
      font-weight: 900;
      text-decoration: none;
      letter-spacing: 1px;
      text-align: center;
      
      animation: fadeInUp 0.8s ease forwards 0.3s;
    }
    </style>
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h3 class="highlight">User Registration</h3>
                    </div>
                    <div class="card-body">
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>
                        <?php if ($success): ?>
                            <div class="alert alert-success"><?php echo $success; ?></div>
                        <?php endif; ?>

                        <form method="POST" action="">
                            <div class="row">
                                <div class="col-md-6">
                                    <h4 class="mb-3">Account Information</h4>
                                    <div class="mb-3">
                                        <label for="user_id" class="form-label">User ID</label>
                                        <input type="text" class="form-control" id="user_id" name="user_id" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="password" class="form-label">Password</label>
                                        <input type="password" class="form-control" id="password" name="password" required minlength="8">
                                        <div class="form-text">Password must be at least 8 characters long</div>
                                    </div>
                                    <div class="mb-3">
                                        <label for="confirm_password" class="form-label">Confirm Password</label>
                                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required minlength="8">
                                    </div>
                                    <div class="mb-3">
                                        <label for="role" class="form-label">Role</label>
                                        <select class="form-select" id="role" name="role" required>
                                            <option value="student">Student</option>
                                            <option value="officer">Officer</option>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label for="department" class="form-label">Department</label>
                                        <input type="text" class="form-control" id="department" name="department">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <h4 class="mb-3">Personal Information</h4>
                                    <div class="mb-3">
                                        <label for="full_name" class="form-label">Full Name</label>
                                        <input type="text" class="form-control" id="full_name" name="full_name">
                                    </div>
                                    <div class="mb-3">
                                        <label for="email" class="form-label">Email</label>
                                        <input type="email" class="form-control" id="email" name="email">
                                    </div>
                                    <div class="mb-3">
                                        <label for="phone" class="form-label">Phone</label>
                                        <input type="tel" class="form-control" id="phone" name="phone">
                                    </div>
                                    <div class="mb-3">
                                        <label for="gender" class="form-label">Gender</label>
                                        <select class="form-select" id="gender" name="gender">
                                            <option value="">Select Gender</option>
                                            <option value="Male">Male</option>
                                            <option value="Female">Female</option>
                                            <option value="Other">Other</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="row mt-3">
                                <div class="col-md-12">
                                    <h4 class="mb-3">Academic Information</h4>
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label for="section_name" class="form-label">Section</label>
                                                <input type="text" class="form-control" id="section_name" name="section_name">
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label for="program_name" class="form-label">Program</label>
                                                <input type="text" class="form-control" id="program_name" name="program_name">
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label for="year_level" class="form-label">Year Level</label>
                                                <select class="form-select" id="year_level" name="year_level">
                                                    <option value="">Select Year</option>
                                                    <option value="1">1st Year</option>
                                                    <option value="2">2nd Year</option>
                                                    <option value="3">3rd Year</option>
                                                    <option value="4">4th Year</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="d-grid gap-2 mt-4">
                                <button type="submit" class="btn btn-primary">Register</button>
                            </div>
                        </form>
                        <div class="text-center mt-3">
                            <p>Already have an account? <a href="login.php" class="text-decoration-none">Login here</a></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 