<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Electoral Commission Dashboard</title>
  <link rel="icon" href="../../img/icon.png"/>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
  <link rel="stylesheet" href="main.css">
</head>
<body style="background: #f6fafd;">
  <!-- Top Bar -->
  <nav class="navbar navbar-expand-lg" style="background: #2563eb; height: 90px;">
    <div class="container-fluid">
      <div class="d-flex align-items-center">
        <img src="../../img/icon.png" alt="Electoral Commission Logo" style="width:48px; height:48px; background:#fff; border-radius:50%; margin-right:10px;">
        <span class="navbar-brand mb-0 h1 text-white" style="font-size:2rem;">Electoral Commission</span>
      </div>
      <div>
        <button class="btn btn-outline-light rounded-pill d-flex align-items-center" style="font-weight:500;">
          <i class="bi bi-person-circle me-2" style="font-size:1.5rem;"></i> OFFICER01
        </button>
      </div>
    </div>
  </nav>

  <div class="d-flex" style="height:calc(100vh - 90px);">
    <!-- Sidebar -->
    <div class="bg-dark text-white p-4" style="width:260px; min-height:100%; box-shadow:2px 0 8px rgba(0,0,0,0.04);">
      <div class="mb-4 d-flex align-items-center justify-content-between">
        <span class="fw-bold" style="font-size:1.4rem;">Dashboard</span>
        <button class="btn btn-secondary btn-sm rounded-circle"><i class="bi bi-chevron-left"></i></button>
      </div>
      <ul class="nav flex-column gap-2">
        <li class="nav-item"><a class="nav-link text-white d-flex align-items-center" href="#"><i class="bi bi-house-door me-2"></i> Home</a></li>
        <li class="nav-item"><a class="nav-link text-white d-flex align-items-center" href="#"><i class="bi bi-people me-2"></i> Candidates</a></li>
        <li class="nav-item"><a class="nav-link text-white d-flex align-items-center" href="#"><i class="bi bi-plus-circle me-2"></i> Add Candidate</a></li>
        <li class="nav-item"><a class="nav-link text-white d-flex align-items-center" href="#"><i class="bi bi-trash me-2"></i> Remove Candidate</a></li>
        <li class="nav-item"><a class="nav-link text-white d-flex align-items-center" href="#"><i class="bi bi-list-check me-2"></i> All Results</a></li>
        <li class="nav-item"><a class="nav-link text-white d-flex align-items-center" href="#"><i class="bi bi-box-arrow-right me-2"></i> Logout</a></li>
      </ul>
    </div>
    <!-- Main Content -->
    <div class="flex-grow-1 p-5 d-flex justify-content-end align-items-start">
      <!-- Calendar Placeholder -->
      <div class="bg-white rounded-4 shadow p-4" style="width:340px;">
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
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
