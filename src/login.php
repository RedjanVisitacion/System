<?php
session_start();
include_once 'connection.php';

// Ensure required fields are set
if (!isset($_POST['id'], $_POST['password'], $_POST['role'])) {
  echo json_encode([
    "success" => false,
    "message" => "Missing required fields.",
    "debug" => $_POST // Show received POST values for debugging
  ]);
  exit();
}

$id = $_POST['id'];
$password = $_POST['password'];
$role = $_POST['role'];
$department = isset($_POST['department']) ? $_POST['department'] : null;

// Validate inputs are not empty
if (empty($id) || empty($password) || empty($role)) {
  echo json_encode(["success" => false, "message" => "All fields are required."]);
  exit();
}

// Check if user already exists
$check_sql = "SELECT * FROM users WHERE id = ?";
$check_stmt = $con->prepare($check_sql);
$check_stmt->bind_param("s", $id);
$check_stmt->execute();
$check_result = $check_stmt->get_result();

if ($check_result->num_rows > 0) {
  echo json_encode(["success" => false, "message" => "User ID already exists."]);
  exit();
}
$check_stmt->close();

// Insert user
$insert_sql = "INSERT INTO users (id, password, role, department) VALUES (?, ?, ?, ?)";
$insert_stmt = $con->prepare($insert_sql);
$insert_stmt->bind_param("ssss", $id, $password, $role, $department);

if ($insert_stmt->execute()) {
  echo json_encode(["success" => true, "message" => "Registration successful."]);
} else {
  echo json_encode(["success" => false, "message" => "Registration failed."]);
}

$insert_stmt->close();
$con->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css"/>
  <title>Register - Voting System</title>
</head>
<body>
  <div class="container py-4">
    <h1 class="text-center mb-4">Register to ELECOM Voting System</h1>

    <div id="roleSelectionForm">
      <h2>Choose Role</h2>
      <div class="d-grid gap-2">
        <button onclick="selectRole('student')" class="btn btn-primary">I am a Student</button>
        <button onclick="selectRole('comelec')" class="btn btn-secondary">I am a COMELEC Officer</button>
      </div>
    </div>

    <div id="registerForm" class="d-none mt-4">
      <h2>Register</h2>
      <input type="text" id="id" class="form-control mb-2" placeholder="Enter ID" />
      <div class="mb-3 position-relative">
        <label for="password" class="form-label">Password</label>
        <input type="password" class="form-control" id="password" name="password" required>
        <span class="position-absolute top-50 end-0 translate-middle-y me-3" style="cursor:pointer;" onclick="togglePassword()">
          <i id="togglePasswordIcon" class="fa fa-eye"></i>
        </span>
      </div>
      <select id="department" class="form-select mb-3">
        <option value="">Select Department</option>
        <option value="IT">IT</option>
        <option value="Food Processing">Food Processing</option>
        <option value="Education">Education</option>
      </select>
      <div class="d-grid gap-2">
        <button onclick="register()" class="btn btn-success">Register</button>
      </div>
    </div>
  </div>

  <script>
    let role = "";

    function selectRole(selectedRole) {
      role = selectedRole;
      document.getElementById("roleSelectionForm").classList.add("d-none");
      document.getElementById("registerForm").classList.remove("d-none");

      if (role === "comelec") {
        document.getElementById("department").classList.add("d-none");
      } else {
        document.getElementById("department").classList.remove("d-none");
      }
    }

    function register() {
      const id = document.getElementById("id").value.trim();
      const password = document.getElementById("password").value.trim();
      const department = document.getElementById("department").value.trim();

      if (!id || !password || (role === 'student' && !department)) {
        alert("Please fill in all required fields.");
        return;
      }

      const data = new URLSearchParams();
      data.append("id", id);
      data.append("password", password);
      data.append("role", role);
      if (role === "student") {
        data.append("department", department);
      }

      fetch("register.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: data
      })
      .then(res => res.json())
      .then(data => {
        if (data.success) {
          alert("Registration successful!");
          window.location.href = "login.html";
        } else {
          alert(data.message);
          console.log("Debug:", data.debug); // 🔍 Show values received by PHP
        }
      })
      .catch(err => {
        console.error(err);
        alert("An error occurred during registration.");
      });
    }

    function togglePassword() {
      const passwordInput = document.getElementById('password');
      const icon = document.getElementById('togglePasswordIcon');
      if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
      } else {
        passwordInput.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
      }
    }
  </script>

</body>
</html>
