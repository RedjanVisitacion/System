<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>ELECOM Voting System Login</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    html, body {
      height: 100%;
    }
    body {
      min-height: 100vh;
      background: linear-gradient(135deg, #e0e7ef 0%, #f5f7fa 100%);
      font-family: 'Inter', sans-serif;
      display: flex;
      align-items: center;
      justify-content: center;
    }
    .main-wrapper {
      min-height: 100vh;
      width: 100vw;
      display: flex;
      align-items: center;
      justify-content: center;
      background: none;
    }
    .login-split {
      display: flex;
      flex-direction: row;
      width: 100vw;
      max-width: 1200px;
      min-height: 80vh;
      align-items: center;
      justify-content: center;
      background: none;
      margin: auto;
      gap: 0;
    }
    .login-image {
      flex: 1 1 0;
      display: flex;
      align-items: center;
      justify-content: center;
      background: #f1f3f6;
      min-height: 100%;
      min-width: 0;
      border-top-left-radius: 32px;
      border-bottom-left-radius: 32px;
      border-top-right-radius: 0;
      border-bottom-right-radius: 0;
      box-shadow: 0 8px 32px rgba(60,60,100,0.07);
    }
    .login-image img {
      width: 240px;
      max-width: 80vw;
      border-radius: 50%;
      box-shadow: 0 4px 24px rgba(0,0,0,0.10);
      background: transparent;
      padding: 0;
      margin: 0 auto;
      display: block;
      transition: box-shadow 0.3s;
    }
    .login-image img:hover {
      box-shadow: 0 8px 32px rgba(37,99,235,0.18);
    }
    .login-form-card {
      flex: 1 1 0;
      background: #fff;
      display: flex;
      flex-direction: column;
      justify-content: center;
      padding: 56px 40px 48px 40px;
      box-shadow: 0 8px 32px rgba(60,60,100,0.13);
      border-radius: 32px;
      max-width: 420px;
      min-width: 320px;
      min-height: 480px;
      margin: 0 auto;
      position: relative;
    }
    .brand {
      font-weight:700;
      font-size:1.1rem;
      letter-spacing: 1px;
      margin-bottom: 24px;
    }
    .welcome-title {
      font-weight: 800;
      font-size: 2.2rem;
      margin-bottom: 0.25rem;
      line-height: 1.1;
      letter-spacing: 0.5px;
    }
    .highlight {
      color: #2563eb;
      background: linear-gradient(90deg, #2563eb 60%, #1e40af 100%);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
      text-fill-color: transparent;
      font-weight: 900;
      text-decoration: underline wavy #2563eb33;
      letter-spacing: 1px;
    }
    .subtitle {
      color: #888;
      margin-bottom: 24px;
      font-size: 1.08rem;
      font-weight: 500;
    }
    .input-group {
      display: flex;
      align-items: center;
      background: #f1f3f6;
      border-radius: 8px;
      padding: 10px 14px;
      margin-bottom: 16px;
      position: relative;
      border: 2px solid transparent;
      transition: border-color 0.2s;
    }
    .input-group input {
      border: none;
      background: transparent;
      outline: none;
      width: 100%;
      font-size: 16px;
      color: #222;
    }
    .input-group input:focus {
      outline: none;
    }
    .input-group:focus-within {
      border-color: #2563eb;
      box-shadow: 0 0 0 2px #2563eb22;
    }
    .input-group.error {
      border-color: #e53935;
      box-shadow: 0 0 0 2px #e5393522;
    }
    .input-error {
      color: #e53935;
      font-size: 0.97rem;
      margin-bottom: 8px;
      margin-top: -10px;
      min-height: 18px;
    }
    .form-options {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 18px;
      font-size: 14px;
      gap: 8px;
    }
    .forgot-link {
      color: #2563eb;
      text-decoration: none;
      font-weight: 500;
      transition: color 0.2s;
      margin-left: auto;
    }
    .forgot-link:hover {
      color: #1e40af;
      text-decoration: underline;
    }
    .btn-primary {
      width: 100%;
      background: linear-gradient(90deg, #2563eb 60%, #1e40af 100%);
      color: #fff;
      border: none;
      padding: 12px;
      border-radius: 8px;
      font-size: 17px;
      margin-bottom: 12px;
      cursor: pointer;
      transition: background 0.2s, transform 0.15s;
      font-weight: 700;
      box-shadow: 0 2px 8px rgba(37,99,235,0.08);
    }
    .btn-primary:hover, .btn-primary:focus {
      background: #1e40af;
      transform: scale(1.05);
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
      color: #888;
    }
    .toggle-password:focus { outline: 2px solid #1a73e8; }
    .toggle-password[aria-label] {
      position: relative;
    }
    .toggle-password[aria-label]:hover::after {
      content: attr(aria-label);
      position: absolute;
      top: -32px;
      right: 0;
      background: #222;
      color: #fff;
      font-size: 0.85rem;
      padding: 2px 8px;
      border-radius: 6px;
      white-space: nowrap;
      z-index: 10;
      opacity: 0.95;
    }
    @keyframes fadeInUp { to { opacity: 1; transform: translateY(0); } }
    @media (max-width: 991.98px) {
      .login-split {
        flex-direction: column;
        min-height: 100vh;
        gap: 0;
        max-width: 100vw;
      }
      .login-image {
        min-height: 180px;
        padding: 32px 0 0 0;
        border-radius: 0;
        border-top-left-radius: 32px;
        border-top-right-radius: 32px;
        border-bottom-left-radius: 0;
        border-bottom-right-radius: 0;
        box-shadow: none;
      }
      .login-form-card {
        margin: 0 auto 32px auto;
        min-width: 0;
        width: 95vw;
        max-width: 420px;
        padding: 32px 16px;
        border-radius: 0 0 32px 32px;
        min-height: 380px;
      }
    }
    @media (max-width: 575.98px) {
      .login-image img {
        width: 120px;
        padding: 0;
      }
      .login-form-card {
        padding: 14px 3vw;
        border-radius: 0 0 24px 24px;
        min-height: 320px;
      }
      .welcome-title {
        font-size: 1.2rem;
      }
    }
  </style>
</head>
<body>
<div class="main-wrapper">
  <div class="login-split">
    <div class="login-image">
      <img src="../../img/icon.png" alt="ELECOM Logo" />
    </div>
    <div class="login-form-card">
      <div class="brand">ELECOM Voting System</div>
      <div class="mb-2">
        <h1 class="welcome-title">Welcome to <span class="highlight">ELECOM</span></h1>
        <span class="subtitle">We make it easy for everyone to vote securely.</span>
      </div>
      <form id="loginForm" autocomplete="off" novalidate>
        <div class="input-group mb-2" id="userIdGroup">
          <label for="userId" class="visually-hidden">User ID</label>
          <input type="text" id="userId" name="userId" placeholder="User ID" required aria-label="User ID" />
        </div>
        <div class="input-error" id="userIdError"></div>
        <div class="input-group mb-2" id="passwordGroup">
          <label for="passwordInput" class="visually-hidden">Password</label>
          <input type="password" id="passwordInput" name="password" placeholder="Password" required aria-label="Password" />
          <span class="toggle-password" id="togglePassword" tabindex="0" aria-label="Show password">
            <svg id="eyeOpen" xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="#888" viewBox="0 0 16 16">
              <path d="M16 8s-3-5.5-8-5.5S0 8 0 8s3 5.5 8 5.5S16 8 16 8zm-8 4.5c-2.485 0-4.5-2.015-4.5-4.5S5.515 3.5 8 3.5s4.5 2.015 4.5 4.5-2.015 4.5-4.5 4.5zm0-7A2.5 2.5 0 1 0 8 11a2.5 2.5 0 0 0 0-5z"/>
            </svg>
            <svg id="eyeClosed" xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="#888" viewBox="0 0 16 16" style="display:none;">
              <path d="M13.359 11.238l2.122 2.122-1.415 1.415-2.122-2.122A7.97 7.97 0 0 1 8 13.5c-5 0-8-5.5-8-5.5a15.634 15.634 0 0 1 3.273-3.746L1.393 2.393 2.808.978l13.435 13.435-1.415 1.415-1.469-1.469zm-1.415-1.415L4.177 3.056A13.134 13.134 0 0 0 1.5 8s3 5.5 8 5.5c1.306 0 2.55-.252 3.693-.707l-1.249-1.249a6.978 6.978 0 0 1-2.944.456c-2.485 0-4.5-2.015-4.5-4.5 0-.98.316-1.885.857-2.627z"/>
            </svg>
          </span>
        </div>
        <div class="input-error" id="passwordError"></div>
        <div class="form-options mb-2">
          <label class="mb-0"><input type="checkbox" /> Remember me</label>
          <a href="#" class="forgot-link">Forgot Password?</a>
        </div>
        <button type="submit" class="btn btn-primary">Login</button>
        <div class="input-error text-center" id="loginError"></div>
      </form>
    </div>
  </div>
</div>
<script>
const loginForm = document.getElementById('loginForm');
const userIdInput = document.getElementById('userId');
const passwordInput = document.getElementById('passwordInput');
const userIdGroup = document.getElementById('userIdGroup');
const passwordGroup = document.getElementById('passwordGroup');
const userIdError = document.getElementById('userIdError');
const passwordError = document.getElementById('passwordError');
const loginError = document.getElementById('loginError');

loginForm.addEventListener('submit', function(e) {
  e.preventDefault();
  let valid = true;
  userIdError.textContent = '';
  passwordError.textContent = '';
  loginError.textContent = '';
  userIdGroup.classList.remove('error');
  passwordGroup.classList.remove('error');

  if (!userIdInput.value.trim()) {
    userIdError.textContent = 'User ID is required.';
    userIdGroup.classList.add('error');
    valid = false;
  }
  if (!passwordInput.value.trim()) {
    passwordError.textContent = 'Password is required.';
    passwordGroup.classList.add('error');
    valid = false;
  }
  if (!valid) return;

  fetch('login_verify.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ userId: userIdInput.value.trim(), password: passwordInput.value.trim() })
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
      loginError.textContent = data.message || 'Login failed. Please try again.';
      userIdGroup.classList.add('error');
      passwordGroup.classList.add('error');
    }
  })
  .catch(() => {
    loginError.textContent = 'A network error occurred.';
  });
});

const togglePassword = document.getElementById('togglePassword');
const eyeOpen = document.getElementById('eyeOpen');
const eyeClosed = document.getElementById('eyeClosed');
togglePassword.addEventListener('click', function() {
  const isPassword = passwordInput.type === 'password';
  passwordInput.type = isPassword ? 'text' : 'password';
  eyeOpen.style.display = isPassword ? 'none' : 'inline';
  eyeClosed.style.display = isPassword ? 'inline' : 'none';
  togglePassword.setAttribute('aria-label', isPassword ? 'Hide password' : 'Show password');
});
togglePassword.addEventListener('keydown', function(e) {
  if (e.key === 'Enter' || e.key === ' ') this.click();
});
userIdInput.addEventListener('focus', () => userIdGroup.classList.remove('error'));
passwordInput.addEventListener('focus', () => passwordGroup.classList.remove('error'));
</script>
</body>
</html>
