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


        .navB{
            margin-left: 20px;
        }
    </style>
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar navbar-dark bg-primary shadow-sm" style="min-height:50px; z-index: 1050;">
        <div class="navB">
            <a href="<?php echo $_SESSION['role'] === 'officer' ? 'dashboard_officer.php' : 'dashboard_student.php'; ?>" class="btn btn-outline-light rounded-pill d-flex align-items-center gap-2 px-3 py-1" style="font-weight:500;">
                <i class="bi bi-arrow-left fs-6"></i>
                <span class="fw-semibold" style="font-size:1rem;">Back</span>
            </a>
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
            <div class="row justify-content-center">
                <div class="col-md-4">
                    <div class="format-option selected" data-format="txt">
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
        
        // Handle report generation
        document.getElementById('generateBtn').addEventListener('click', function() {
            const startDate = document.getElementById('startDate').value;
            const endDate = document.getElementById('endDate').value;
            const generateBtn = this;
            const loadingSpinner = generateBtn.querySelector('.loading-spinner');
            const downloadLink = document.getElementById('downloadLink');
            const errorMessage = document.getElementById('errorMessage');

            // Show loading state
            generateBtn.disabled = true;
            loadingSpinner.style.display = 'inline-block';
            downloadLink.style.display = 'none';
            errorMessage.style.display = 'none';

            // Prepare form data
            const formData = new FormData();
            formData.append('format', selectedFormat);
            formData.append('start_date', startDate);
            formData.append('end_date', endDate);

            // Send request to generate report
            fetch('../function/generate_report.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                // Check if the response is a file download
                const contentType = response.headers.get('content-type');
                if (contentType && (contentType.includes('application/json') || contentType.includes('text/plain'))) {
                    return response.blob();
                }
                return response.json();
            })
            .then(data => {
                if (data instanceof Blob) {
                    // Handle file download
                    const url = window.URL.createObjectURL(data);
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = `election_report_${new Date().toISOString().slice(0,10)}.${selectedFormat}`;
                    document.body.appendChild(a);
                    a.click();
                    window.URL.revokeObjectURL(url);
                    a.remove();
                    
                    // Show success message
                    const successMessage = document.createElement('div');
                    successMessage.className = 'alert alert-success mt-3';
                    successMessage.innerHTML = '<i class="fas fa-check-circle me-2"></i>Report downloaded successfully';
                    document.querySelector('.report-card').appendChild(successMessage);
                    setTimeout(() => successMessage.remove(), 3000);
                } else if (data.success) {
                    // Show success message
                    const successMessage = document.createElement('div');
                    successMessage.className = 'alert alert-success mt-3';
                    successMessage.innerHTML = '<i class="fas fa-check-circle me-2"></i>' + data.message;
                    document.querySelector('.report-card').appendChild(successMessage);
                    setTimeout(() => successMessage.remove(), 3000);
                } else {
                    throw new Error(data.message || 'Failed to generate report');
                }
            })
            .catch(error => {
                // Show error message
                errorMessage.textContent = error.message;
                errorMessage.style.display = 'block';
                errorMessage.classList.add('animate__animated', 'animate__fadeIn');
            })
            .finally(() => {
                // Reset button state
                generateBtn.disabled = false;
                loadingSpinner.style.display = 'none';
            });
        });
    </script>
</body>
</html> 