<?php
require_once 'check_session.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Officer Dashboard - ELECOM</title>
    <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <!-- Custom CSS -->
  <style>
        :root {
            --primary-color: #2563eb;
            --secondary-color: #1e40af;
            --sidebar-width: 250px;
            --header-height: 60px;
        }

    body {
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            background-color: #f8fafc;
            overflow-x: hidden;
        }

        /* Header Styles */
    .navbar {
            height: var(--header-height);
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .navbar-brand {
            font-weight: 600;
            font-size: 1.25rem;
        }

        /* Sidebar Styles */
        .sidebar {
            width: var(--sidebar-width);
            height: calc(100vh - var(--header-height));
      position: fixed;
            top: var(--header-height);
      left: 0;
            background: #fff;
            box-shadow: 2px 0 8px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            z-index: 1000;
        }

        .sidebar.collapsed {
            width: 70px;
        }

        .sidebar-header {
            padding: 1rem;
            border-bottom: 1px solid #e5e7eb;
        }

    .nav-link {
            padding: 0.75rem 1rem;
            color: #4b5563;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            transition: all 0.2s ease;
        }

    .nav-link:hover, .nav-link.active {
            background-color: #f3f4f6;
            color: var(--primary-color);
        }

        .nav-link i {
            font-size: 1.25rem;
            min-width: 24px;
        }

        /* Main Content Styles */
        .main-content {
            margin-left: var(--sidebar-width);
            padding: 1.5rem;
            transition: all 0.3s ease;
        }

        .main-content.expanded {
            margin-left: 70px;
        }

        /* Dashboard Cards */
    .dashboard-card {
      background: #fff;
            border-radius: 1rem;
            padding: 1.5rem;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            transition: transform 0.2s ease;
    }

    .dashboard-card:hover {
            transform: translateY(-5px);
        }

        .card-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 1rem;
      background: #e0e7ff;
            color: var(--primary-color);
        }

        /* Responsive Design */
    @media (max-width: 991.98px) {
            .sidebar {
        transform: translateX(-100%);
            }

            .sidebar.show {
        transform: translateX(0);
      }

      .main-content {
        margin-left: 0;
            }

            .overlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
                right: 0;
                bottom: 0;
                background: rgba(0,0,0,0.5);
                z-index: 999;
            }

            .overlay.show {
        display: block;
      }
    }

        @media (max-width: 767.98px) {
            .dashboard-card {
                margin-bottom: 1rem;
            }

      .navbar-brand {
                font-size: 1rem;
            }

            .main-content {
                padding: 1rem;
            }
        }

        /* Calendar Styles */
      .calendar-card {
            background: #fff;
            border-radius: 1rem;
            padding: 1.5rem;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        }

        .calendar-table {
            width: 100%;
        }

        .calendar-table th,
        .calendar-table td {
            text-align: center;
            padding: 0.5rem;
        }

        .calendar-table .today {
            background-color: var(--primary-color);
            color: #fff;
            border-radius: 50%;
    }
  </style>
</head>
<body>
    <!-- Header -->
    <nav class="navbar navbar-expand-lg navbar-dark fixed-top">
    <div class="container-fluid">
            <button class="btn btn-link text-white d-lg-none me-1" id="sidebarToggle">
                <i class="bi bi-list"></i>
        </button>
            <a class="navbar-brand d-flex align-items-center" href="#" style="gap: 0.5rem;">
                <img src="../../img/icon.png" alt="ELECOM" style="width: 28px; height: 28px;">
                <span>ELECOM</span>
            </a>
            <div class="ms-auto d-flex align-items-center">
                <span class="text-white me-2"><?php echo htmlspecialchars($_SESSION['user_id']); ?></span>
                <a href="logout.php" class="btn btn-outline-light btn-sm">
                    <i class="bi bi-box-arrow-right"></i>
                </a>
      </div>
    </div>
  </nav>

      <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <h5 class="mb-0">Dashboard</h5>
        </div>
        <ul class="nav flex-column">
          <li class="nav-item">
                <a class="nav-link active" href="officer_dashboard.php">
              <i class="bi bi-house-door"></i>
                    <span>Home</span>
            </a>
          </li>
          <li class="nav-item">
                <a class="nav-link" href="candidates.php">
              <i class="bi bi-people"></i>
                    <span>Candidates</span>
            </a>
          </li>
          <li class="nav-item">
                <a class="nav-link" href="#" data-bs-toggle="modal" data-bs-target="#addCandidateModal">
              <i class="bi bi-plus-circle"></i>
                    <span>Add Candidate</span>
            </a>
          </li>
          <li class="nav-item">
                <a class="nav-link" href="remove_candidate.php">
              <i class="bi bi-trash"></i>
                    <span>Remove Candidate</span>
            </a>
          </li>
          <li class="nav-item">
                <a class="nav-link" href="results.php">
              <i class="bi bi-list-check"></i>
                    <span>Results</span>
            </a>
          </li>
          <li class="nav-item">
                <a class="nav-link" href="generate_report.php">
              <i class="bi bi-file-earmark-bar-graph"></i>
                    <span>Reports</span>
            </a>
          </li>
        </ul>
      </div>

    <!-- Overlay -->
    <div class="overlay"></div>

      <!-- Main Content -->
    <div class="main-content">
        <div class="container-fluid">
        <div class="row g-4">
                <!-- Dashboard Cards -->
                <div class="col-12 col-md-6 col-lg-3">
                    <div class="dashboard-card">
                        <div class="card-icon">
                            <i class="bi bi-people"></i>
                        </div>
                        <h3 class="h5 mb-2">Total Candidates</h3>
                        <p class="h3 mb-0" id="totalCandidates">0</p>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-lg-3">
                    <div class="dashboard-card">
                        <div class="card-icon">
                            <i class="bi bi-person-check"></i>
                        </div>
                        <h3 class="h5 mb-2">Total Voters</h3>
                        <p class="h3 mb-0" id="totalVoters">0</p>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-lg-3">
                    <div class="dashboard-card">
                        <div class="card-icon">
                            <i class="bi bi-check-circle"></i>
                        </div>
                        <h3 class="h5 mb-2">Votes Cast</h3>
                        <p class="h3 mb-0" id="votesCast">0</p>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-lg-3">
                    <div class="dashboard-card">
                        <div class="card-icon">
                            <i class="bi bi-clock"></i>
                        </div>
                        <h3 class="h5 mb-2">Time Remaining</h3>
                        <p class="h3 mb-0" id="timeRemaining">--:--:--</p>
                    </div>
                </div>

                <!-- Calendar Section -->
                <div class="col-12 col-lg-4">
                    <div class="calendar-card">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="mb-0">Calendar</h5>
                            <div class="btn-group">
                                <button class="btn btn-sm btn-outline-primary" id="prevMonthBtn">
                                    <i class="bi bi-chevron-left"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-primary" id="todayBtn">Today</button>
                                <button class="btn btn-sm btn-outline-primary" id="nextMonthBtn">
                                    <i class="bi bi-chevron-right"></i>
                                </button>
                            </div>
                        </div>
                        <h6 class="text-center mb-3" id="calendarMonthYear"></h6>
                        <table class="table table-bordered calendar-table">
                            <thead>
                                <tr>
                                    <th>Su</th>
                                    <th>Mo</th>
                                    <th>Tu</th>
                                    <th>We</th>
                                    <th>Th</th>
                                    <th>Fr</th>
                                    <th>Sa</th>
                                </tr>
                            </thead>
                            <tbody id="calendarBody"></tbody>
                        </table>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Add Candidate Modal -->
    <div class="modal fade" id="addCandidateModal" tabindex="-1">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
                    <h5 class="modal-title">Add Candidate</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <form id="addCandidateForm">
                        <div class="mb-3">
                            <label for="candidateName" class="form-label">Candidate Name</label>
                            <input type="text" class="form-control" id="candidateName" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="candidatePosition" class="form-label">Position</label>
                            <select class="form-select" id="candidatePosition" name="position" required>
                                <option value="">Select Position</option>
                                <option value="president">President</option>
                                <option value="vice_president">Vice President</option>
                                <option value="secretary">Secretary</option>
                                <option value="treasurer">Treasurer</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Add Candidate</button>
          </form>
          <div id="addCandidateMsg" class="mt-2"></div>
        </div>
      </div>
    </div>
  </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JS -->
  <script>
        // Sidebar Toggle
        document.getElementById('sidebarToggle').addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('show');
            document.querySelector('.overlay').classList.toggle('show');
        });

        document.querySelector('.overlay').addEventListener('click', function() {
            document.querySelector('.sidebar').classList.remove('show');
            this.classList.remove('show');
        });

        // Calendar Functionality
  const calendarMonthYear = document.getElementById('calendarMonthYear');
  const calendarBody = document.getElementById('calendarBody');
  const prevMonthBtn = document.getElementById('prevMonthBtn');
  const nextMonthBtn = document.getElementById('nextMonthBtn');
  const todayBtn = document.getElementById('todayBtn');

  let today = new Date();
  let currentMonth = today.getMonth();
  let currentYear = today.getFullYear();

  function renderCalendar(month, year) {
    const monthNames = [
      'January', 'February', 'March', 'April', 'May', 'June',
      'July', 'August', 'September', 'October', 'November', 'December'
    ];
    calendarMonthYear.textContent = `${monthNames[month]} ${year}`;

    const firstDay = new Date(year, month, 1).getDay();
    const daysInMonth = new Date(year, month + 1, 0).getDate();

    let date = 1;
    let rows = '';
            for (let i = 0; i < 6; i++) {
      let row = '<tr>';
      for (let j = 0; j < 7; j++) {
        if (i === 0 && j < firstDay) {
                        row += '<td></td>';
        } else if (date > daysInMonth) {
                        row += '<td></td>';
        } else {
          let isToday = (date === today.getDate() && month === today.getMonth() && year === today.getFullYear());
                        row += `<td class="${isToday ? 'today' : ''}">${date}</td>`;
          date++;
        }
      }
      row += '</tr>';
      rows += row;
      if (date > daysInMonth) break;
    }
    calendarBody.innerHTML = rows;
  }

  renderCalendar(currentMonth, currentYear);

  prevMonthBtn.onclick = function() {
    currentMonth--;
    if (currentMonth < 0) {
      currentMonth = 11;
      currentYear--;
    }
    renderCalendar(currentMonth, currentYear);
  };

  nextMonthBtn.onclick = function() {
    currentMonth++;
    if (currentMonth > 11) {
      currentMonth = 0;
      currentYear++;
    }
    renderCalendar(currentMonth, currentYear);
  };

  todayBtn.onclick = function() {
    currentMonth = today.getMonth();
    currentYear = today.getFullYear();
    renderCalendar(currentMonth, currentYear);
  };

        // Add Candidate Form
        document.getElementById('addCandidateForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            
            fetch('add_candidate.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                const msg = document.getElementById('addCandidateMsg');
                if (data.success) {
                    msg.className = 'alert alert-success';
                    this.reset();
                } else {
                    msg.className = 'alert alert-danger';
                }
                msg.textContent = data.message;
            })
            .catch(error => {
                document.getElementById('addCandidateMsg').className = 'alert alert-danger';
                document.getElementById('addCandidateMsg').textContent = 'An error occurred. Please try again.';
            });
  });
  </script>
</body>
</html>