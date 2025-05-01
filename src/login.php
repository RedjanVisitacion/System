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


<div class="login-container">
  <div class="login-image">
    <!-- Replace with your image -->
    <img src="../../img/icon.png" alt="Building" />
  </div>
  <div class="login-form-card">
    <div class="brand">
      <span>ELECOM Voting System</span>
    </div>
    <h2>Welcome to <span class="highlight">ELECOM</span></h2>
    <p class="subtitle">We make it easy for everyone to vote securely.</p>
    <form>
      <div class="input-group">
        <i class="bi bi-person"></i>
        <input type="text" placeholder="User ID" required />
      </div>
      <div class="input-group">
        <i class="bi bi-lock"></i>
        <input type="password" placeholder="Password" id="passwordInput" required />
        <span class="toggle-password" id="togglePassword" tabindex="0">
          <svg id="eyeOpen" xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="#888" viewBox="0 0 16 16">
            <path d="M16 8s-3-5.5-8-5.5S0 8 0 8s3 5.5 8 5.5S16 8 16 8zm-8 4.5c-2.485 0-4.5-2.015-4.5-4.5S5.515 3.5 8 3.5s4.5 2.015 4.5 4.5-2.015 4.5-4.5 4.5zm0-7A2.5 2.5 0 1 0 8 11a2.5 2.5 0 0 0 0-5z"/>
          </svg>
          <svg id="eyeClosed" xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="#888" viewBox="0 0 16 16" style="display:none;">
            <path d="M13.359 11.238l2.122 2.122-1.415 1.415-2.122-2.122A7.97 7.97 0 0 1 8 13.5c-5 0-8-5.5-8-5.5a15.634 15.634 0 0 1 3.273-3.746L1.393 2.393 2.808.978l13.435 13.435-1.415 1.415-1.469-1.469zm-1.415-1.415L4.177 3.056A13.134 13.134 0 0 0 1.5 8s3 5.5 8 5.5c1.306 0 2.55-.252 3.693-.707l-1.249-1.249a6.978 6.978 0 0 1-2.944.456c-2.485 0-4.5-2.015-4.5-4.5 0-.98.316-1.885.857-2.627z"/>
          </svg>
        </span>
      </div>
      <div class="form-options">
        <label><input type="checkbox" /> Remember me</label>
        <a href="#">Forgot password?</a>
      </div>
      <button type="submit" class="btn-primary">Login</button>
    </form>
   
  </div>
</div>

<style>
body {
  font-family: 'Inter', sans-serif;
  background: #f5f7fa;
}
.login-container {
  display: flex;
  min-height: 100vh;
}
.login-image {
  flex: 1;
  background: #e9ecef;
  display: flex;
  align-items: center;
  justify-content: center;
}
.login-image img {
  width: 300px;
  max-width: 80%;
  border-radius: 50%;
  box-shadow: 0 2px 16px rgba(0,0,0,0.06);
  background: #fff;
  padding: 24px;
}
.login-form-card {
  flex: 1;
  background: #fff;
  display: flex;
  flex-direction: column;
  justify-content: center;
  padding: 60px 40px;
  box-shadow: 0 8px 32px rgba(60,60,100,0.08);
  border-radius: 20px;
  max-width: 400px;
  margin: auto;
  opacity: 0;
  transform: translateY(40px);
  animation: fadeInUp 0.8s cubic-bezier(.23,1.01,.32,1) forwards;
}
.brand {
  display: flex;
  align-items: center;
  margin-bottom: 24px;
}
.brand .logo {
  width: 40px;
  margin-right: 12px;
}
h2 {
  font-weight: 700;
  margin-bottom: 8px;
}
.highlight {
  color: #1a73e8;
}
.subtitle {
  color: #888;
  margin-bottom: 24px;
}
.input-group {
  display: flex;
  align-items: center;
  background: #f1f3f6;
  border-radius: 8px;
  padding: 10px 14px;
  margin-bottom: 16px;
  position: relative;
}
.input-group i {
  color: #888;
  margin-right: 10px;
}
.input-group input {
  border: none;
  background: transparent;
  outline: none;
  width: 100%;
  font-size: 16px;
}
.form-options {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 24px;
  font-size: 14px;
}
.btn-primary {
  width: 100%;
  background: #1a73e8;
  color: #fff;
  border: none;
  padding: 12px;
  border-radius: 8px;
  font-size: 16px;
  margin-bottom: 12px;
  cursor: pointer;
  transition: background 0.2s;
}
.btn-primary:hover {
  background: #1765c1;
}
.btn-secondary {
  width: 100%;
  background: #fff;
  color: #1a73e8;
  border: 1px solid #1a73e8;
  padding: 12px;
  border-radius: 8px;
  font-size: 16px;
  cursor: pointer;
  transition: background 0.2s, color 0.2s;
}
.btn-secondary:hover {
  background: #f1f3f6;
}
.social-login {
  text-align: center;
  margin-top: 24px;
  color: #888;
}
.social-login a {
  margin: 0 8px;
  color: #1a73e8;
  text-decoration: none;
}
.social-login a:hover {
  text-decoration: underline;
}
@media (max-width: 900px) {
  .login-container {
    flex-direction: column;
    align-items: center;
    justify-content: center;
  }
  .login-image {
    margin: 32px 0 0 0;
    flex: none;
  }
  .login-form-card {
    max-width: 95vw;
    width: 100%;
    min-width: 0;
    margin: 32px 0;
    padding: 32px 10vw;
  }
  .login-image img {
    width: 180px;
    padding: 12px;
  }
}

@keyframes fadeInUp {
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.toggle-password {
  position: absolute;
  right: 16px;
  top: 50%;
  transform: translateY(-50%);
  cursor: pointer;
  user-select: none;
  display: flex;
  align-items: center;
}
.toggle-password:focus {
  outline: 2px solid #1a73e8;
}
</style>

<script>
document.querySelector('form').addEventListener('submit', function(e) {
    e.preventDefault();
    const userId = document.querySelector('input[placeholder="User ID"]').value.trim();
    const password = document.querySelector('input[placeholder="Password"]').value.trim();

    fetch('login_verify.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ userId, password })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            if (data.role === 'officer') {
                window.location.href = 'dashboard_officer.html';
            } else {
                window.location.href = 'dashboard_student.html';
            }
        } else {
            alert(data.message);
        }
    })
    .catch(() => {
        alert('A network error occurred.');
    });
});

const passwordInput = document.getElementById('passwordInput');
const togglePassword = document.getElementById('togglePassword');
const eyeOpen = document.getElementById('eyeOpen');
const eyeClosed = document.getElementById('eyeClosed');

togglePassword.addEventListener('click', function() {
  const isPassword = passwordInput.type === 'password';
  passwordInput.type = isPassword ? 'text' : 'password';
  eyeOpen.style.display = isPassword ? 'none' : 'inline';
  eyeClosed.style.display = isPassword ? 'inline' : 'none';
});
togglePassword.addEventListener('keydown', function(e) {
  if (e.key === 'Enter' || e.key === ' ') this.click();
});
</script>
