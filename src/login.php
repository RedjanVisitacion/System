
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>ELECOM Voting System Login</title>
  <style>
    body { font-family: 'Inter', sans-serif; background: #f5f7fa; }
    .login-container { display: flex; min-height: 100vh; }
    .login-image { flex: 1; background: #e9ecef; display: flex; align-items: center; justify-content: center; }
    .login-image img { width: 300px; max-width: 80%; border-radius: 50%; box-shadow: 0 2px 16px rgba(0,0,0,0.06); background: #fff; padding: 24px; }
    .login-form-card { flex: 1; background: #fff; display: flex; flex-direction: column; justify-content: center; padding: 60px 40px; box-shadow: 0 8px 32px rgba(60,60,100,0.08); border-radius: 20px; max-width: 400px; margin: auto; opacity: 0; transform: translateY(40px); animation: fadeInUp 0.8s cubic-bezier(.23,1.01,.32,1) forwards; }
    .brand { display: flex; align-items: center; margin-bottom: 24px; }
    h2 { font-weight: 700; margin-bottom: 8px; }
    .highlight { color: #1a73e8; }
    .subtitle { color: #888; margin-bottom: 24px; }
    .input-group { display: flex; align-items: center; background: #f1f3f6; border-radius: 8px; padding: 10px 14px; margin-bottom: 16px; position: relative; }
    .input-group i { color: #888; margin-right: 10px; }
    .input-group input { border: none; background: transparent; outline: none; width: 100%; font-size: 16px; }
    .form-options { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; font-size: 14px; }
    .btn-primary { width: 100%; background: #1a73e8; color: #fff; border: none; padding: 12px; border-radius: 8px; font-size: 16px; margin-bottom: 12px; cursor: pointer; transition: background 0.2s; }
    .btn-primary:hover { background: #1765c1; }
    @keyframes fadeInUp { to { opacity: 1; transform: translateY(0); } }
    .toggle-password { position: absolute; right: 16px; top: 50%; transform: translateY(-50%); cursor: pointer; user-select: none; display: flex; align-items: center; }
    .toggle-password:focus { outline: 2px solid #1a73e8; }
  </style>
</head>
<body>
<div class="login-container">
  <div class="login-image">
    <img src="../../img/icon.png" alt="Building" />
  </div>
  <div class="login-form-card">
    <div class="brand">
      <span>ELECOM Voting System</span>
    </div>
    <h2>Welcome to <span class="highlight">ELECOM</span></h2>
    <p class="subtitle">We make it easy for everyone to vote securely.</p>
    <form id="loginForm" autocomplete="off">
      <div class="input-group">
        <i class="bi bi-person"></i>
        <input type="text" id="userId" placeholder="User ID" required />
      </div>
      <div class="input-group">
        <i class="bi bi-lock"></i>
        <input type="password" id="passwordInput" placeholder="Password" required />
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
<script>
document.getElementById('loginForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const userId = document.getElementById('userId').value.trim();
    const password = document.getElementById('passwordInput').value.trim();

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
</body>
</html>
