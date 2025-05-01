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
      min-height: 100vh;
      width: 100vw;
      margin: 0;
      padding: 0;
      box-sizing: border-box;
      font-family: 'Inter', 'Roboto', Arial, sans-serif;
      background: linear-gradient(135deg, #e0e7ef 0%, #f5f7fa 100%);
      display: flex;
      align-items: center;
      justify-content: center;
      overflow-x: hidden;
    }
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
    .login-page-container {
      display: flex;
      align-items: stretch;
      justify-content: center;
      width: 100%;
      max-width: 520px;
      margin: 0 auto;
      border-radius: 28px;
      box-shadow: 0 8px 40px rgba(60,60,100,0.13), 0 2px 8px rgba(37,99,235,0.08);
      overflow: hidden;
      background: rgba(255,255,255,0.15);
      backdrop-filter: blur(8px);
    }
    .login-logo-side {
      display: flex;
      align-items: center;
      justify-content: center;
      background: rgba(255,255,255,0.35);
      border-right: 1px solid rgba(200, 220, 255, 0.18);
      padding: 32px 18px;
      min-width: 0;
      max-width: 240px;
      width: 240px;
      border-radius: 28px 0 0 28px;
      box-shadow: 0 2px 16px rgba(37,99,235,0.04);
      position: relative;
      backdrop-filter: blur(16px);
    }
    .elecom-logo-img {
      width: 100%;
      max-width: 110px;
      height: auto;
      object-fit: contain;
      padding: 0;
      display: block;
      margin: 0 auto;
      filter: drop-shadow(0 2px 8px rgba(37,99,235,0.08));
    }
    .login-form-card {
      background: linear-gradient(135deg, rgba(10, 77, 231, 0.84) 0%, rgba(187, 201, 221, 0.3) 100%);
      border: 1.5px solid rgba(0, 0, 0, 0.18);
      box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.18);
      backdrop-filter: blur(18px);
      -webkit-backdrop-filter: blur(18px);
      display: flex;
      flex-direction: column;
      justify-content: center;
      align-items: center;
      padding: 48px 32px 40px 32px;
      width: 100%;
      min-width: 0;
      min-height: 320px;
      position: relative;
      transition: all 0.3s cubic-bezier(.4,0,.2,1);
    }
    .welcome-title {
      font-weight: 700;
      font-size: 2rem;
      margin-bottom: 0.5rem;
      line-height: 1.1;
      letter-spacing: 0.5px;
      text-align: center;
      text-decoration: none;
      color: #222;
    }
    .highlight {
      color: #2563eb;
      background: linear-gradient(90deg,rgb(240, 242, 245) 0%,rgb(52, 66, 104) 50%, #1e40af 100%);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
      text-fill-color: transparent;
      font-weight: 900;
      text-decoration: none;
      letter-spacing: 1px;
      text-shadow: 0 2px 4px rgba(37,99,235,0.08);
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
      background: rgba(255,255,255,0.55);
      border-radius: 10px;
      margin-bottom: 18px;
      position: relative;
      border: 1.5px solid #b6d0ff;
      transition: all 0.3s cubic-bezier(.4,0,.2,1);
      height: 44px;
      padding: 0;
      width: 100%;
      max-width: 320px;
      box-shadow: 0 1px 4px rgba(37,99,235,0.04);
    }
    .input-group input {
      border: none;
      background: transparent;
      outline: none;
      width: 100%;
      font-size: 15px;
      color: #222;
      padding: 0 44px 0 16px;
      border-radius: 10px;
      transition: all 0.3s cubic-bezier(.4,0,.2,1);
      height: 100%;
      box-sizing: border-box;
      background: transparent;
    }
    .input-group input:focus {
      outline: none;
      background: #eaf1ff;
      box-shadow: 0 0 0 2px #2563eb44;
    }
    .input-group:hover {
      border-color: #2563eb33;
      background: #f8fafc;
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
      margin-bottom: 22px;
      font-size: 14px;
      gap: 12px;
      width: 100%;
      max-width: 320px;
      padding: 0 2px;
    }
    .forgot-link {
      color:rgba(51, 53, 56, 0.59);
      text-decoration: none;
      font-weight: 500;
      transition: color 0.2s;
      margin-left: auto;
      font-size: 14px;
    }
    .forgot-link:hover {
      color: #1e40af;
      text-decoration: underline;
    }
    .btn-primary {
      width: 100%;
      max-width: 320px;
      background: linear-gradient(90deg,rgb(105, 161, 194) 0%,rgb(10, 81, 173) 100%);
      color: #fff;
      border: none;
      padding: 12px;
      border-radius: 10px;
      font-size: 16px;
      margin-bottom: 10px;
      cursor: pointer;
      transition: all 0.3s cubic-bezier(.4,0,.2,1);
      font-weight: 600;
      box-shadow: 0 2px 8px rgba(37,99,235,0.10);
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      height: 44px;
      position: relative;
      overflow: hidden;
    }
    .btn-primary:hover {
      background: linear-gradient(90deg, #60a5fa 0%, #3b82f6 50%, #2563eb 100%);
      transform: translateY(-1px) scale(1.01);
      box-shadow: 0 6px 16px rgba(37,99,235,0.13);
    }
    .btn-primary:active {
      transform: translateY(1px) scale(0.99);
      box-shadow: 0 2px 8px rgba(37,99,235,0.10);
    }
    .btn-primary:disabled {
      opacity: 0.7;
      cursor: not-allowed;
      transform: none;
    }
    .btn-content {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      transition: all 0.3s cubic-bezier(.4,0,.2,1);
    }
    .btn-primary.loading .btn-content {
      opacity: 0;
    }
    .loading-spinner {
      position: absolute;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%);
      width: 20px;
      height: 20px;
      border: 3px solid rgba(255, 255, 255, 0.3);
      border-radius: 50%;
      border-top-color: #fff;
      animation: spin 1s linear infinite;
      opacity: 0;
      transition: opacity 0.3s cubic-bezier(.4,0,.2,1);
    }
    .btn-primary.loading .loading-spinner {
      opacity: 1;
    }
    @keyframes spin {
      0% { transform: translate(-50%, -50%) rotate(0deg); }
      100% { transform: translate(-50%, -50%) rotate(360deg); }
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
    @media (max-width: 1200px) {
      .login-page-container {
        max-width: 98vw;
        gap: 24px;
        padding: 32px 4vw;
      }
      .login-logo-side {
        max-width: 180px;
      }
      .login-form-card {
        max-width: 98vw;
      }
    }
    @media (max-width: 900px) {
      .login-page-container {
        flex-direction: column;
        max-width: 400px;
        margin: 0 auto;
        align-items: center;
        gap: 0;
        padding: 0;
      }
      .login-logo-side {
        width: 100%;
        max-width: 100%;
        border-right: none;
        border-bottom: none;
        border-radius: 20px 20px 0 0;
        padding: 0;
        margin: 0;
        justify-content: center;
        align-items: center;
        display: flex;
        box-shadow: none;
        text-align: center;
        background: transparent;
      }
      .elecom-logo-img {
        display: block;
        margin: 0 auto;
        text-align: center;
        float: none;
        max-width: 100px;
      }
      .login-form-card {
        width: 100%;
        padding: 0 0 32px 0;
        border-radius: 0 0 20px 20px;
        margin: 0;
        box-shadow: 0 2px 16px rgba(37,99,235,0.08);
      }
      .welcome-title {
        font-size: 1.5rem;
      }
      .subtitle {
        font-size: 1rem;
      }
      .input-group {
        width: 90%;
        max-width: 320px;
      }
      .form-options {
        width: 90%;
        max-width: 320px;
      }
      .btn-primary {
        width: 90%;
        max-width: 320px;
      }
    }
    @media (max-width: 768px) {
      .login-page-container {
        flex-direction: column;
        gap: 24px;
        padding: 24px 16px;
      }
      .login-logo-side {
        width: 90%;
        max-width: 320px;
        margin: 0 auto 24px auto;
        padding: 30px;
      }
      .login-form-card {
        width: 100%;
        max-width: 320px;
        padding: 32px 24px;
      }
      .welcome-title {
        font-size: 1.75rem;
      }
    }
    @media (max-width: 600px) {
      .login-page-container {
        margin: 0;
        border-radius: 20px;
        max-width: 100%;
        gap: 0;
        padding: 0;
        align-items: center;
        background: rgba(255, 255, 255, 0.1);
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        backdrop-filter: blur(8px);
      }
      .login-logo-side {
        padding: 0;
        border-radius: 20px 20px 0 0;
        margin: 0;
        justify-content: center;
        align-items: center;
        display: flex;
        text-align: center;
        background: rgba(255, 255, 255, 0.15);
        border: 1px solid rgba(255, 255, 255, 0.2);
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        width: 100%;
      }
      .elecom-logo-img {
        display: block;
        margin: 0 auto;
        text-align: center;
        float: none;
        max-width: 120px;
      }
      .login-form-card {
        padding: 0 0 24px 0;
        border-radius: 0 0 20px 20px;
        margin: 0;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        background: rgba(255, 255, 255, 0.15);
        border: 1px solid rgba(255, 255, 255, 0.2);
        backdrop-filter: blur(8px);
        width: 100%;
      }
      .welcome-title {
        font-size: 1.75rem;
      }
    }
    @media (max-width: 480px) {
      .login-page-container {
        padding: 8px 15px;
        background: rgba(255, 255, 255, 0.1);
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        backdrop-filter: blur(8px);
        width: 100%;
      }
      .login-form-card {
        padding: 15px;
        width: 100%;
        max-width: 100%;
        margin: 0 auto !important;
        background: rgba(255, 255, 255, 0.15);
        border: 1px solid rgba(255, 255, 255, 0.2);
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        backdrop-filter: blur(8px);
      }
      .login-logo-side {
        width: 100%;
        max-width: 100%;
        padding: 10px;
        background: rgba(255, 255, 255, 0.15);
        border: 1px solid rgba(255, 255, 255, 0.2);
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
      }
      .welcome-title {
        font-size: 1.1rem;
      }
      .form-options {
        flex-direction: column;
        align-items: flex-start;
        gap: 12px;
      }
      .forgot-link {
        margin-left: 0;
      }
      .input-group {
        margin: 0 auto 16px auto;
        width: 95%;
      }
    }
    /* Enhanced dual ring spinner for loading overlay */
    .dual-ring-spinner {
      display: inline-block;
      width: 56px;
      height: 56px;
      position: relative;
    }
    .dual-ring-spinner:after {
      content: " ";
      display: block;
      width: 44px;
      height: 44px;
      margin: 6px;
      border-radius: 50%;
      border: 6px solid #2563eb;
      border-color: #2563eb transparent #60a5fa transparent;
      animation: dual-ring-spin 1.2s linear infinite;
      box-shadow: 0 0 16px 2px #2563eb55;
    }
    @keyframes dual-ring-spin {
      0% { transform: rotate(0deg); }
      100% { transform: rotate(360deg); }
    }
    .loading-glow {
      text-shadow: 0 0 8px #2563eb88, 0 0 16px #60a5fa44;
    }
  </style>
</head>
<body>
  <div class="login-page-container">
    <div class="login-logo-side">
      <img src="../img/elecback.png" alt="ELECOM Logo" class="elecom-logo-img">
    </div>
    <div class="login-form-card">
      <div class="mb-2">
        <h1 class="welcome-title">Welcome to <span class="highlight">ELECOM</span></h1>
        <span class="subtitle">‎ </span>
       
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
          <label class="mb-0"><input type="checkbox" id="rememberMe" /> Remember me</label>
    
        </div>
        <button type="submit" class="btn btn-primary" id="loginBtn">
          <span id="loginBtnText">Login</span>
          <span id="loginSpinner" class="spinner" style="display:none;"></span>
        </button>
        <div class="input-error text-center" id="loginError"></div>
      </form>
    </div>
  </div>
  <div id="loginLoadingOverlay" style="display:none;position:fixed;z-index:9999;top:0;left:0;width:100vw;height:100vh;background:rgba(37,99,235,0.18);backdrop-filter:blur(4px);align-items:center;justify-content:center;flex-direction:column;">
    <div style="background:rgba(255,255,255,0.97);padding:44px 36px 36px 36px;border-radius:20px;box-shadow:0 4px 32px rgba(37,99,235,0.13);display:flex;flex-direction:column;align-items:center;">
      <div class="dual-ring-spinner" style="margin-bottom:22px;"></div>
      <div class="loading-glow" style="font-size:1.25rem;font-weight:700;color:#2563eb;letter-spacing:1px;">Logging in...</div>
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
const loginBtn = document.getElementById('loginBtn');
const loginBtnText = document.getElementById('loginBtnText');
const loginSpinner = document.getElementById('loginSpinner');
const rememberMe = document.getElementById('rememberMe');
const loginLoadingOverlay = document.getElementById('loginLoadingOverlay');

// On page load, check localStorage for remembered userId
window.addEventListener('DOMContentLoaded', () => {
  const rememberedUserId = localStorage.getItem('rememberedUserId');
  if (rememberedUserId) {
    userIdInput.value = rememberedUserId;
    rememberMe.checked = true;
  }
});

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

  // Remember me logic
  if (rememberMe.checked) {
    localStorage.setItem('rememberedUserId', userIdInput.value.trim());
  } else {
    localStorage.removeItem('rememberedUserId');
  }

  loginBtn.disabled = true;
  loginBtn.classList.add('loading');

  fetch('login_verify.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ userId: userIdInput.value.trim(), password: passwordInput.value.trim() })
  })
  .then(res => res.json())
  .then(data => {
    loginBtn.disabled = false;
    loginBtn.classList.remove('loading');
    if (data.success) {
      // Show loading overlay and delay redirect
      loginLoadingOverlay.style.display = 'flex';
      setTimeout(() => {
        if (data.role === 'officer') {
          window.location.href = 'dashboard_officer.php';
        } else {
          window.location.href = 'dashboard_student.php';
        }
      }, 1000);
    } else {
      loginError.textContent = data.message || 'Login failed. Please try again.';
      userIdGroup.classList.add('error');
      passwordGroup.classList.add('error');
    }
  })
  .catch(() => {
    loginBtn.disabled = false;
    loginBtn.classList.remove('loading');
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
