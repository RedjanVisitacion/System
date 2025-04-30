<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Electoral Commission Dashboard</title>
  <link rel="icon" href="../../img/icon.png"/>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
  <link rel="stylesheet" href="main.css">
  <meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<body style="background: #f6fafd;">
  <!-- Top Bar -->
  <nav class="navbar navbar-expand-lg" style="background: #2563eb; height: 60px;">
    <div class="container-fluid">
      <div class="d-flex align-items-center">
        <img src="../../img/icon.png" alt="Electoral Commission Logo" style="width:36px; height:36px; background:#fff; border-radius:50%; margin-right:10px;">
        <span class="navbar-brand mb-0 h1 text-white" style="font-size:1.5rem;">Electoral Commission</span>
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
    <div class="bg-dark text-white p-4" style="width:260px; min-height:100vh; box-shadow:2px 0 8px rgba(0,0,0,0.04);">
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
    <div class="flex-grow-1 p-4">
      <div class="row g-4">
        <!-- Function Cards -->
        <div class="col-12 col-lg-8">
          <div class="d-flex flex-wrap gap-4 justify-content-center justify-content-lg-start">
            <!-- Function Card: Total Candidates -->
            <div class="bg-white rounded-4 shadow p-4 text-center flex-fill" style="min-width:180px; max-width:220px;">
              <div class="mb-2"><i class="bi bi-people" style="font-size:2rem; color:#2563eb;"></i></div>
              <div class="fw-bold" style="font-size:1.2rem;">Total Candidates</div>
              <div class="fs-4">12</div>
            </div>
            <!-- Function Card: Add Candidate -->
            <a href="#" class="bg-white rounded-4 shadow p-4 text-center text-decoration-none flex-fill" style="min-width:180px; max-width:220px;" data-bs-toggle="modal" data-bs-target="#addCandidateModal">
              <div class="mb-2"><i class="bi bi-plus-circle" style="font-size:2rem; color:#2563eb;"></i></div>
              <div class="fw-bold" style="font-size:1.2rem;">Add Candidate</div>
            </a>
            <!-- Function Card: Remove Candidate -->
            <a href="#" class="bg-white rounded-4 shadow p-4 text-center text-decoration-none flex-fill" style="min-width:180px; max-width:220px;">
              <div class="mb-2"><i class="bi bi-trash" style="font-size:2rem; color:#2563eb;"></i></div>
              <div class="fw-bold" style="font-size:1.2rem;">Remove Candidate</div>
            </a>
            <!-- Function Card: All Results -->
            <a href="#" class="bg-white rounded-4 shadow p-4 text-center text-decoration-none flex-fill" style="min-width:180px; max-width:220px;">
              <div class="mb-2"><i class="bi bi-list-check" style="font-size:2rem; color:#2563eb;"></i></div>
              <div class="fw-bold" style="font-size:1.2rem;">All Results</div>
            </a>
          </div>
        </div>
        <!-- Calendar -->
        <div class="col-12 col-lg-4 d-flex justify-content-center justify-content-lg-end">
          <div class="bg-white rounded-4 shadow p-4" style="width:340px; max-width:100%;">
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
      .then(r => r.text())
      .then(response => {
          if (response.trim() === 'success') {
              msg.textContent = 'Candidate added!';
              msg.className = 'text-success mt-2';
              form.reset();
          } else if (response.trim() === 'empty') {
              msg.textContent = 'Please enter a name.';
              msg.className = 'text-danger mt-2';
          } else {
              msg.textContent = 'Error adding candidate.';
              msg.className = 'text-danger mt-2';
          }
      })
      .catch(() => {
          msg.textContent = 'Error connecting to server.';
          msg.className = 'text-danger mt-2';
      });
  };
  </script>
</body>
</html>
