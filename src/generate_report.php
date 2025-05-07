<?php
require_once 'connection.php';
require_once 'check_session.php';

// Get user profile picture and full name
$stmt = $con->prepare("SELECT profile_picture, full_name FROM user_profile WHERE user_id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$userData = $result->fetch_assoc();
$profilePicture = $userData['profile_picture'] ?? '../img/icon.png';
$fullName = $userData['full_name'] ?? 'User';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generate Report - Election System</title>
    <link rel="icon" href="../img/icon.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <style>
        .report-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            padding: 2rem;
            margin-top: 2rem;
        }
        .format-option {
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .format-option:hover {
            border-color: #0d6efd;
            background-color: #f8f9fa;
        }
        .format-option.selected {
            border-color: #0d6efd;
            background-color: #e7f1ff;
        }
        .format-option i {
            font-size: 2rem;
            margin-bottom: 1rem;
            color: #0d6efd;
        }
        .date-range-container {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        .date-range-container label {
            font-weight: 500;
            color: #495057;
        }
        .date-range-container .form-control {
            border-radius: 8px;
        }
        .generate-btn {
            padding: 0.8rem 2rem;
            font-size: 1.1rem;
            border-radius: 8px;
            background: #0d6efd;
            color: white;
            border: none;
            transition: all 0.3s ease;
        }
        .generate-btn:hover {
            background: #0b5ed7;
            transform: translateY(-2px);
        }
        .generate-btn:disabled {
            background: #6c757d;
            cursor: not-allowed;
        }
        .loading-spinner {
            display: none;
            margin-left: 1rem;
        }
        .download-link {
            display: none;
            margin-top: 1rem;
            padding: 0.8rem 1.5rem;
            background: #198754;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        .download-link:hover {
            background: #157347;
            color: white;
        }
        .error-message {
            display: none;
            color: #dc3545;
            margin-top: 1rem;
            padding: 1rem;
            background: #f8d7da;
            border-radius: 8px;
        }
    </style>
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="dashboard_officer.php">
                <i class="fas fa-vote-yea me-2"></i>
                Election System
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboar_officer.php">
                            <i class="fas fa-home me-1"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="generate_report.php">
                            <i class="fas fa-file-alt me-1"></i> Generate Report
                        </a>
                    </li>
                </ul>
                <div class="d-flex align-items-center">
                    <span class="text-white me-3"><?php echo htmlspecialchars($fullName); ?></span>
                    <!--<img src="img/<?php echo htmlspecialchars($profilePicture); ?>" 
                         alt="Profile" 
                         class="rounded-circle"
                         style="width: 40px; height: 40px; object-fit: cover;">-->
                    
                         <img src="../img/icon.png" 
                         alt="Profile" 
                         class="rounded-circle"
                         style="width: 40px; height: 40px; object-fit: cover;">
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container">
        <div class="report-card">
            <h2 class="mb-4">Generate Election Report</h2>
            
            <!-- Date Range Selection -->
            <div class="date-range-container">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="startDate" class="form-label">Start Date</label>
                        <input type="text" class="form-control" id="startDate" placeholder="Select start date">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="endDate" class="form-label">End Date</label>
                        <input type="text" class="form-control" id="endDate" placeholder="Select end date">
                    </div>
                </div>
            </div>
            
            <!-- Report Format Selection -->
            <h5 class="mb-3">Select Report Format</h5>
            <div class="row">
                <div class="col-md-6">
                    <div class="format-option" data-format="json">
                        <i class="fas fa-code"></i>
                        <h5>JSON Format</h5>
                        <p class="text-muted">Machine-readable format suitable for data processing</p>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="format-option" data-format="txt">
                        <i class="fas fa-file-alt"></i>
                        <h5>Text Format</h5>
                        <p class="text-muted">Human-readable format with formatted text</p>
                    </div>
                </div>
            </div>
            
            <!-- Generate Button -->
            <div class="text-center mt-4">
                <button class="generate-btn" id="generateBtn" disabled>
                    Generate Report
                    <span class="loading-spinner">
                        <i class="fas fa-spinner fa-spin"></i>
                    </span>
                </button>
            </div>
            
            <!-- Download Link -->
            <div class="text-center">
                <a href="#" class="download-link" id="downloadLink" target="_blank">
                    <i class="fas fa-download me-2"></i> Download Report
                </a>
            </div>
            
            <!-- Error Message -->
            <div class="error-message" id="errorMessage"></div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
        // Initialize date pickers
        flatpickr("#startDate", {
            dateFormat: "Y-m-d",
            maxDate: "today",
            onChange: function(selectedDates) {
                endDatePicker.set("minDate", selectedDates[0]);
                validateForm();
            }
        });
        
        const endDatePicker = flatpickr("#endDate", {
            dateFormat: "Y-m-d",
            maxDate: "today",
            onChange: function() {
                validateForm();
            }
        });
        
        // Format selection
        let selectedFormat = null;
        document.querySelectorAll('.format-option').forEach(option => {
            option.addEventListener('click', function() {
                document.querySelectorAll('.format-option').forEach(opt => {
                    opt.classList.remove('selected');
                });
                this.classList.add('selected');
                selectedFormat = this.dataset.format;
                validateForm();
            });
        });
        
        // Form validation
        function validateForm() {
            const startDate = document.getElementById('startDate').value;
            const endDate = document.getElementById('endDate').value;
            const generateBtn = document.getElementById('generateBtn');
            
            generateBtn.disabled = !(startDate && endDate && selectedFormat);
        }
        
        // Generate report
        document.getElementById('generateBtn').addEventListener('click', async function() {
            const startDate = document.getElementById('startDate').value;
            const endDate = document.getElementById('endDate').value;
            const generateBtn = this;
            const loadingSpinner = document.querySelector('.loading-spinner');
            const downloadLink = document.getElementById('downloadLink');
            const errorMessage = document.getElementById('errorMessage');
            
            // Reset UI
            downloadLink.style.display = 'none';
            errorMessage.style.display = 'none';
            generateBtn.disabled = true;
            loadingSpinner.style.display = 'inline-block';
            
            try {
                const response = await fetch('../src/generate_report.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        format: selectedFormat,
                        start_date: startDate,
                        end_date: endDate
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    downloadLink.href = data.filepath;
                    downloadLink.style.display = 'inline-block';
                } else {
                    errorMessage.textContent = data.message;
                    errorMessage.style.display = 'block';
                }
            } catch (error) {
                errorMessage.textContent = 'An error occurred while generating the report.';
                errorMessage.style.display = 'block';
            } finally {
                generateBtn.disabled = false;
                loadingSpinner.style.display = 'none';
            }
        });
    </script>
</body>
</html> 