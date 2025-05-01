<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <link rel="icon" href="../img/icon.png"/>
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
    .login-form-card {
      background: #fff;
      display: flex;
      flex-direction: column;
      justify-content: center;
      align-items: center;
      padding: 48px 28px 36px 28px;
      box-shadow: 0 8px 32px rgba(60,60,100,0.13), 0 2px 8px rgba(37,99,235,0.10);
      border-radius: 24px;
      max-width: 370px;
      width: 100%;
      margin: 0 auto;
      min-width: 0;
      min-height: 420px;
      position: relative;
      transition: box-shadow 0.2s, transform 0.2s;
    }
    .login-form-card:hover {
      box-shadow: 0 12px 40px rgba(37,99,235,0.18), 0 2px 8px rgba(37,99,235,0.13);
      transform: translateY(-2px) scale(1.01);
    }
    .welcome-title {
      font-weight: 800;
      font-size: 2rem;
      margin-bottom: 0.25rem;
      line-height: 1.1;
      letter-spacing: 0.5px;
      text-align: center;
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
      text-align: center;
    }
    .input-group {
      display: flex;
      align-items: center;
      background: #f1f3f6;
      border-radius: 8px;
      margin-bottom: 16px;
      position: relative;
      border: 2px solid transparent;
      transition: border-color 0.2s;
      height: 44px;
      padding: 0;
    }
    .input-group input {
      border: none;
      background: transparent;
      outline: none;
      width: 100%;
      font-size: 15px;
      color: #222;
      padding: 0 44px 0 12px;
      border-radius: 6px;
      transition: background 0.2s, box-shadow 0.2s;
      height: 100%;
      box-sizing: border-box;
      background: transparent;
    }
    .input-group input:focus {
      outline: none;
      background: #eaf1ff;
      box-shadow: 0 0 0 2px #2563eb44;
    }
    .input-group.error {
      border-color: #e53935;
      background: #fff0f0;
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
      width: 100%;
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
      padding: 14px;
      border-radius: 10px;
      font-size: 19px;
      margin-bottom: 12px;
      cursor: pointer;
      transition: background 0.2s, transform 0.15s, box-shadow 0.2s;
      font-weight: 700;
      box-shadow: 0 2px 8px rgba(37,99,235,0.10);
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 10px;
    }
    .btn-primary:disabled {
      opacity: 0.7;
      cursor: not-allowed;
    }
    .toggle-password {
      position: absolute;
      right: 12px;
      top: 50%;
      transform: translateY(-50%);
      cursor: pointer;
      user-select: none;
      display: flex;
      align-items: center;
      color: #888;
      font-size: 1.2em;
      background: none;
      border: none;
      padding: 0;
      height: 24px;
      width: 24px;
      z-index: 2;
      transition: color 0.2s;
    }
    .toggle-password:hover, .toggle-password:focus {
      color: #2563eb;
      background: none;
    }
    .input-group.error .toggle-password {
      background: none;
    }
    .spinner {
      border: 3px solid #f3f3f3;
      border-top: 3px solid #2563eb;
      border-radius: 50%;
      width: 18px;
      height: 18px;
      animation: spin 1s linear infinite;
      display: inline-block;
    }
    @keyframes spin {
      0% { transform: rotate(0deg); }
      100% { transform: rotate(360deg); }
    }
    @media (max-width: 575.98px) {
      .login-form-card {
        padding: 14px 3vw;
        border-radius: 16px;
        min-height: 320px;
      }
      .welcome-title {
        font-size: 1.2rem;
      }
      .btn-primary {
        font-size: 16px;
        padding: 12px;
      }
      .input-group input {
        font-size: 16px;
        padding: 10px 44px 10px 10px;
      }
    }
  </style>
</head>
<body>
  <div class="login-form-card">
    <div class="mb-2">
      <h1 class="welcome-title">Welcome to <span class="highlight">ELECOM</span></h1>
      <span class="subtitle">We make it easy for everyone to vote securely.</span>
    </div>
    <form id="loginForm" autocomplete="off" novalidate style="width:100%">
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
      <button type="submit" class="btn btn-primary" id="loginBtn">
        <span id="loginBtnText">Login</span>
        <span id="loginSpinner" class="spinner" style="display:none;"></span>
      </button>
      <div class="input-error text-center" id="loginError"></div>
    </form>
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
const loginBtn = document.getElementById('loginBtn');
const loginBtnText = document.getElementById('loginBtnText');
const loginSpinner = document.getElementById('loginSpinner');

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

  loginBtn.disabled = true;
  loginBtnText.style.display = 'none';
  loginSpinner.style.display = 'inline-block';

  fetch('login_verify.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ userId: userIdInput.value.trim(), password: passwordInput.value.trim() })
  })
  .then(res => res.json())
  .then(data => {
    loginBtn.disabled = false;
    loginBtnText.style.display = 'inline';
    loginSpinner.style.display = 'none';
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
    loginBtn.disabled = false;
    loginBtnText.style.display = 'inline';
    loginSpinner.style.display = 'none';
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
