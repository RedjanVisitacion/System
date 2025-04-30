<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Electoral Commission Dashboard</title>
  <link rel="icon" href="../img/icon.png"/>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="main.css">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <style>
    body {
      font-family: 'Poppins', 'Segoe UI', Arial, sans-serif;
      background: #f6fafd;
    }
    .navbar {
      box-shadow: 0 2px 8px rgba(37, 99, 235, 0.08);
    }
    .bg-dark {
      background: linear-gradient(135deg, #232526 0%, #2563eb 100%) !important;
    }
    .nav-link {
      transition: background 0.2s, color 0.2s;
      border-radius: 8px;
      padding: 10px 16px;
    }
    .nav-link:hover, .nav-link.active {
      background: #2563eb;
      color: #fff !important;
    }
    .sidebar-header {
      font-size: 1.4rem;
      font-weight: 600;
      letter-spacing: 1px;
    }
    .dashboard-card {
      border-radius: 18px !important;
      box-shadow: 0 4px 24px rgba(37, 99, 235, 0.07);
      transition: transform 0.15s, box-shadow 0.15s;
      background: #fff;
      margin-bottom: 24px;
    }
    .dashboard-card:hover {
      transform: translateY(-4px) scale(1.03);
      box-shadow: 0 8px 32px rgba(37, 99, 235, 0.12);
    }
    .dashboard-card .icon {
      display: flex;
      align-items: center;
      justify-content: center;
      width: 48px;
      height: 48px;
      border-radius: 50%;
      background: #e0e7ff;
      margin: 0 auto 12px auto;
      font-size: 2rem;
      color: #2563eb;
    }
    .dashboard-card .fw-bold {
      font-size: 1.2rem;
    }
    .dashboard-card .fs-4 {
      font-size: 2rem;
      font-weight: 600;
      color: #2563eb;
    }
    .btn-primary {
      background: linear-gradient(90deg, #2563eb 60%, #1e40af 100%);
      border: none;
      border-radius: 12px;
      font-weight: 600;
      transition: background 0.2s, transform 0.1s;
    }
    .btn-primary:hover {
      background: #1e40af;
      transform: scale(1.04);
    }
    .table {
      border-radius: 16px;
      overflow: hidden;
    }
    .bg-primary {
      background: #2563eb !important;
    }
    .text-success {
      color: #22c55e !important;
    }
    .text-danger {
      color: #ef4444 !important;
    }
    #sidebar.collapsed {
      width: 70px !important;
      min-width: 70px !important;
      transition: width 0.2s;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
    }
    #sidebar.collapsed ul.nav {
      align-items: center;
      flex-direction: column;
      width: 100%;
      justify-content: center;
      height: 100%;
      display: flex;
    }
    #sidebar.collapsed .nav-link {
      justify-content: center !important;
      padding-left: 0 !important;
      padding-right: 0 !important;
    }
    #sidebar.collapsed .nav-item {
      width: 100%;
      display: flex;
      justify-content: center;
    }
    #sidebar.collapsed .sidebar-text {
      display: none;
    }
    #sidebar.collapsed .nav-link i {
      margin: 0 !important;
      display: flex;
      justify-content: center;
      width: 100%;
    }
  </style>
</head>
<body>
  <!-- Top Bar -->
  <nav class="navbar navbar-expand-lg" style="background: #2563eb; height: 60px;">
    <div class="container-fluid">
      <div class="d-flex align-items-center">
        <img src="../img/icon.png" alt="Electoral Commission Logo" style="width:44px; height:44px; background:#fff; border-radius:50%; margin-right:14px; box-shadow:0 2px 8px rgba(37,99,235,0.10);">
        <span class="navbar-brand mb-0 h1 text-white" style="font-size:1.5rem;">Electoral Commission</span>
      </div>
      <div>
        <button class="btn btn-outline-light rounded-pill d-flex align-items-center" style="font-weight:500;">
          <i class="bi bi-person-circle me-2" style="font-size:1.5rem;"></i> OFFICER01
        </button>
      </div>
    </div>
  </nav>

  <div class="container-fluid p-0">
    <div class="row g-0 flex-nowrap">
      <!-- Sidebar -->
      <div id="sidebar" class="col-auto col-md-3 col-xl-2 bg-dark text-white p-4 min-vh-100 d-flex flex-column" style="box-shadow:2px 0 8px rgba(0,0,0,0.04);">
        <div class="mb-4 d-flex align-items-center justify-content-between">
          <span class="fw-bold sidebar-header sidebar-text">Dashboard</span>
          <button id="sidebarToggle" class="btn btn-secondary btn-sm rounded-circle d-none d-md-inline"><i class="bi bi-chevron-left"></i></button>
        </div>
        <ul class="nav flex-column gap-2">
          <li class="nav-item">
            <a class="nav-link text-white d-flex align-items-center" href="#">
              <i class="bi bi-house-door"></i>
              <span class="sidebar-text">Home</span>
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link text-white d-flex align-items-center" href="#">
              <i class="bi bi-people"></i>
              <span class="sidebar-text">Candidates</span>
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link text-white d-flex align-items-center" href="#">
              <i class="bi bi-plus-circle"></i>
              <span class="sidebar-text">Add Candidate</span>
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link text-white d-flex align-items-center" href="#">
              <i class="bi bi-trash"></i>
              <span class="sidebar-text">Remove Candidate</span>
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link text-white d-flex align-items-center" href="#">
              <i class="bi bi-list-check"></i>
              <span class="sidebar-text">All Results</span>
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link text-white d-flex align-items-center" href="#">
              <i class="bi bi-box-arrow-right"></i>
              <span class="sidebar-text">Logout</span>
            </a>
          </li>
        </ul>
      </div>
      <!-- Main Content -->
      <div class="col px-0 px-md-4 py-4 flex-grow-1">
        <div class="row g-4">
          <!-- Function Cards -->
          <div class="col-12 col-lg-8">
            <div class="row g-4 justify-content-center justify-content-lg-start">
              <!-- Function Card: Total Candidates -->
              <div class="col-12 col-sm-6 col-md-4">
                <div class="dashboard-card p-4 text-center h-100">
                  <div class="icon mb-2"><i class="bi bi-people"></i></div>
                  <div class="fw-bold">Total Candidates</div>
                  <div class="fs-4">12</div>
                </div>
              </div>
              <!-- Function Card: Add Candidate -->
              <div class="col-12 col-sm-6 col-md-4">
                <a href="#" class="dashboard-card p-4 text-center text-decoration-none h-100 d-block" data-bs-toggle="modal" data-bs-target="#addCandidateModal">
                  <div class="icon mb-2"><i class="bi bi-plus-circle"></i></div>
                  <div class="fw-bold">Add Candidate</div>
                </a>
              </div>
              <!-- Function Card: Remove Candidate -->
              <div class="col-12 col-sm-6 col-md-4">
                <a href="#" class="dashboard-card p-4 text-center text-decoration-none h-100 d-block">
                  <div class="icon mb-2"><i class="bi bi-trash"></i></div>
                  <div class="fw-bold">Remove Candidate</div>
                </a>
              </div>
              <!-- Function Card: All Results -->
              <div class="col-12 col-sm-6 col-md-4">
                <a href="#" class="dashboard-card p-4 text-center text-decoration-none h-100 d-block">
                  <div class="icon mb-2"><i class="bi bi-list-check"></i></div>
                  <div class="fw-bold">All Results</div>
                </a>
              </div>
            </div>
          </div>
          <!-- Calendar -->
          <div class="col-12 col-lg-4 d-flex justify-content-center justify-content-lg-end mt-4 mt-lg-0">
            <div class="bg-white rounded-4 shadow p-4 w-100" style="max-width:340px;">
              <div class="d-flex align-items-center justify-content-between mb-3">
                <h4 class="fw-bold mb-0">April 2025</h4>
                <div>
                  <button class="btn btn-primary btn-sm me-1"><i class="bi bi-chevron-left"></i></button>
                  <button class="btn btn-secondary btn-sm me-1">Today</button>
                  <button class="btn btn-primary btn-sm"><i class="bi bi-chevron-right"></i></button>
                </div>
              </div>
              <table class="table table-borderless text-center mb-0">
                <thead>
                  <tr class="fw-bold text-secondary">
                    <th>Sun</th><th>Mon</th><th>Tue</th><th>Wed</th><th>Thu</th><th>Fri</th><th>Sat</th>
                  </tr>
                </thead>
                <tbody>
                  <tr><td class="bg-light rounded-2"></td><td class="bg-light rounded-2">1</td><td class="bg-light rounded-2">2</td><td class="bg-light rounded-2">3</td><td class="bg-light rounded-2">4</td><td class="bg-light rounded-2">5</td><td class="bg-light rounded-2"></td></tr>
                  <tr><td class="bg-light rounded-2">6</td><td class="bg-light rounded-2">7</td><td class="bg-light rounded-2">8</td><td class="bg-light rounded-2">9</td><td class="bg-light rounded-2">10</td><td class="bg-light rounded-2">11</td><td class="bg-light rounded-2">12</td></tr>
                  <tr><td class="bg-light rounded-2">13</td><td class="bg-light rounded-2">14</td><td class="bg-light rounded-2">15</td><td class="bg-light rounded-2">16</td><td class="bg-light rounded-2">17</td><td class="bg-light rounded-2">18</td><td class="bg-light rounded-2">19</td></tr>
                  <tr><td class="bg-light rounded-2">20</td><td class="bg-light rounded-2">21</td><td class="bg-light rounded-2">22</td><td class="bg-light rounded-2">23</td><td class="bg-light rounded-2">24</td><td class="bg-light rounded-2">25</td><td class="bg-light rounded-2">26</td></tr>
                  <tr><td class="bg-light rounded-2">27</td><td class="bg-primary text-white rounded-2">28</td><td class="bg-light rounded-2">29</td><td class="bg-light rounded-2">30</td><td class="bg-light rounded-2"></td><td class="bg-light rounded-2"></td><td class="bg-light rounded-2"></td></tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script src="../function/dashboard.js"></script>
  <!-- Add Candidate Modal -->
  <div class="modal fade" id="addCandidateModal" tabindex="-1" aria-labelledby="addCandidateModalLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="addCandidateModalLabel">Add Candidate</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <form id="addCandidateForm">
            <input type="text" class="form-control mb-2" name="name" placeholder="Candidate Name" required>
            <button type="submit" class="btn btn-primary w-100">Add</button>
          </form>
          <div id="addCandidateMsg" class="mt-2"></div>
        </div>
      </div>
    </div>
  </div>
  <script>
  document.getElementById('addCandidateForm').onsubmit = function(e) {
      e.preventDefault();
      var form = this;
      var msg = document.getElementById('addCandidateMsg');
      msg.textContent = '';
      var formData = new FormData(form);
      fetch('add_candidate.php', {
          method: 'POST',
          body: formData
      })
      .then(r => r.json())
      .then(response => {
          if (response.success) {
              msg.textContent = response.message || 'Candidate added!';
              msg.className = 'text-success mt-2';
              form.reset();
          } else {
              msg.textContent = response.message || 'Error adding candidate.';
              msg.className = 'text-danger mt-2';
          }
      })
      .catch(() => {
          msg.textContent = 'Error connecting to server.';
          msg.className = 'text-danger mt-2';
      });
  };
  document.getElementById('sidebarToggle').onclick = function() {
    document.getElementById('sidebar').classList.toggle('collapsed');
  };
  </script>
</body>
</html>
