<?php
require_once 'connection.php';
require_once 'check_session.php';

// Get user profile picture and full name
$stmt = $con->prepare("SELECT profile_picture, full_name FROM elecom_user_profile WHERE user_id = ?");
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
        body {
            background-color: #f8f9fa;
            min-height: 100vh;
        }
        .report-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.1);
            padding: 2.5rem;
            margin: 2rem auto;
            max-width: 900px;
            transition: transform 0.3s ease;
        }
        .report-card:hover {
            transform: translateY(-5px);
        }
        .format-option {
            border: 2px solid #e9ecef;
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 1.5rem;
            cursor: pointer;
            transition: all 0.3s ease;
            background: white;
        }
        .format-option:hover {
            border-color: #0d6efd;
            background-color: #f8f9fa;
            transform: translateY(-3px);
        }
        .format-option.selected {
            border-color: #0d6efd;
            background-color: #e7f1ff;
            box-shadow: 0 4px 12px rgba(13, 110, 253, 0.15);
        }
        .format-option i {
            font-size: 2.5rem;
            margin-bottom: 1.5rem;
            color: #0d6efd;
            transition: transform 0.3s ease;
        }
        .format-option:hover i {
            transform: scale(1.1);
        }
        .date-range-container {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }
        .date-range-container label {
            font-weight: 600;
            color: #495057;
            margin-bottom: 0.5rem;
        }
        .date-range-container .form-control {
            border-radius: 10px;
            padding: 0.8rem 1rem;
            border: 2px solid #e9ecef;
            transition: all 0.3s ease;
        }
        .date-range-container .form-control:focus {
            border-color: #0d6efd;
            box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.15);
        }
        .generate-btn {
            padding: 1rem 2.5rem;
            font-size: 1.2rem;
            font-weight: 600;
            border-radius: 12px;
            background: linear-gradient(45deg, #0d6efd, #0a58ca);
            color: white;
            border: none;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(13, 110, 253, 0.2);
        }
        .generate-btn:hover {
            background: linear-gradient(45deg, #0a58ca, #084298);
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(13, 110, 253, 0.3);
        }
        .generate-btn:disabled {
            background: #6c757d;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }
        .loading-spinner {
            display: none;
            margin-left: 1rem;
        }
        .download-link {
            display: none;
            margin-top: 1.5rem;
            padding: 1rem 2rem;
            background: linear-gradient(45deg, #198754, #157347);
            color: white;
            text-decoration: none;
            border-radius: 12px;
            transition: all 0.3s ease;
            font-weight: 600;
            box-shadow: 0 4px 12px rgba(25, 135, 84, 0.2);
        }
        .download-link:hover {
            background: linear-gradient(45deg, #157347, #146c43);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(25, 135, 84, 0.3);
        }
        .error-message {
            display: none;
            color: #dc3545;
            margin-top: 1.5rem;
            padding: 1.2rem;
            background: #f8d7da;
            border-radius: 12px;
            border-left: 4px solid #dc3545;
        }
        .success-message {
            display: none;
            color: #198754;
            margin-top: 1.5rem;
            padding: 1.2rem;
            background: #d1e7dd;
            border-radius: 12px;
            border-left: 4px solid #198754;
        }
        .navB {
            margin-left: 20px;
        }
        .page-title {
            color: #212529;
            font-weight: 700;
            margin-bottom: 1.5rem;
            position: relative;
            padding-bottom: 0.5rem;
        }
        .page-title::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 60px;
            height: 4px;
            background: #0d6efd;
            border-radius: 2px;
        }
        .section-title {
            color: #495057;
            font-weight: 600;
            margin-bottom: 1.5rem;
        }
        /* Enhanced Back Button Styles */
        .back-btn {
            position: relative;
            overflow: hidden;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            z-index: 1;
            transform-origin: left center;
        }
        
        .back-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.15);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            z-index: -1;
            border-radius: 50px;
        }
        
        .back-btn:hover::before {
            left: 0;
            width: 100%;
        }
        
        .back-btn i {
            transition: transform 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            display: inline-block;
        }
        
        .back-btn:hover i {
            transform: translateX(-4px);
        }

        .back-btn span {
            transition: transform 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            display: inline-block;
        }

        .back-btn:hover span {
            transform: translateX(-2px);
        }

        /* Enhanced Page Transitions */
        .page-transition {
            animation: fadeInUp 0.6s cubic-bezier(0.4, 0, 0.2, 1);
            opacity: 0;
            animation-fill-mode: forwards;
        }

        @keyframes fadeInUp {
            0% {
                opacity: 0;
                transform: translateY(30px);
            }
            100% {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Enhanced Container Transition */
        .container {
            animation: slideInRight 0.6s cubic-bezier(0.4, 0, 0.2, 1);
            opacity: 0;
            animation-fill-mode: forwards;
            animation-delay: 0.2s;
        }

        @keyframes slideInRight {
            0% {
                opacity: 0;
                transform: translateX(-30px);
            }
            100% {
                opacity: 1;
                transform: translateX(0);
            }
        }

        /* Report Card Animation */
        .report-card {
            animation: scaleIn 0.6s cubic-bezier(0.4, 0, 0.2, 1);
            opacity: 0;
            animation-fill-mode: forwards;
            animation-delay: 0.4s;
        }

        @keyframes scaleIn {
            0% {
                opacity: 0;
                transform: scale(0.95);
            }
            100% {
                opacity: 1;
                transform: scale(1);
            }
        }

        /* Format Option Animation */
        .format-option {
            animation: fadeIn 0.6s cubic-bezier(0.4, 0, 0.2, 1);
            opacity: 0;
            animation-fill-mode: forwards;
            animation-delay: 0.6s;
        }

        @keyframes fadeIn {
            0% {
                opacity: 0;
            }
            100% {
                opacity: 1;
            }
        }

        /* Page Exit Animation */
        .page-exit {
            animation: fadeOutUp 0.5s cubic-bezier(0.4, 0, 0.2, 1) forwards;
        }

        @keyframes fadeOutUp {
            0% {
                opacity: 1;
                transform: translateY(0);
            }
            100% {
                opacity: 0;
                transform: translateY(-30px);
            }
        }

        @media print {
            body * {
                visibility: hidden;
            }
            #generatedReport, #generatedReport * {
                visibility: visible;
            }
            #generatedReport {
                position: absolute;
                left: 0;
                top: 0;
                width: 100vw;
            }
        }
    </style>
</head>
<body class="page-transition">


        <audio src="assets/resultBG.mp3" autoplay loop hidden></audio>

    <!-- Navigation Bar -->
    <nav class="navbar navbar-dark bg-primary shadow-sm" style="background: linear-gradient(90deg, rgb(26, 57, 119), rgb(72, 74, 80)); height: 60px;">
        <div class="navB">
            <a href="<?php echo $_SESSION['role'] === 'officer' ? 'dashboard_officer.php' : 'dashboard_student.php'; ?>" 
               class="btn btn-outline-light rounded-pill d-flex align-items-center gap-2 px-3 py-1 back-btn" 
               style="font-weight:500;"
               onclick="handleBackClick(event)">
                <i class="bi bi-arrow-left fs-6"></i>
                <span class="fw-semibold" style="font-size:1rem;">Back</span>
            </a>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container">
        <div class="report-card">
            <h2 class="page-title">Generate Election Report</h2>
            
            <!-- Date Range Selection -->
            <div class="date-range-container">
                <h5 class="section-title">Select Date Range</h5>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="startDate" class="form-label">
                            <i class="fas fa-calendar-alt me-2"></i>Start Date
                        </label>
                        <input type="text" class="form-control" id="startDate" placeholder="Select start date">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="endDate" class="form-label">
                            <i class="fas fa-calendar-alt me-2"></i>End Date
                        </label>
                        <input type="text" class="form-control" id="endDate" placeholder="Select end date">
                    </div>
                </div>
            </div>
            
            <!-- Report Format Selection -->
            <h5 class="section-title">Select Report Format</h5>
            <div class="row justify-content-center">
                <div class="col-md-6">
                    <div class="format-option" data-format="txt">
                        <i class="fas fa-file-alt"></i>
                        <h5>Text Format</h5>
                        <p class="text-muted">Human-readable format with formatted text</p>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="format-option selected" data-format="pdf">
                        <i class="fas fa-file-pdf"></i>
                        <h5>PDF Format</h5>
                        <p class="text-muted">Printable document with professional layout</p>
                    </div>
                </div>
            </div>
            
            <!-- Generate Button -->
            <div class="text-center mt-4">
                <button class="generate-btn" id="generateBtn" disabled>
                    <i class="fas fa-file-export me-2"></i>Generate Report
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
            
            <!-- Messages -->
            <div class="error-message" id="errorMessage"></div>
            <div class="success-message" id="successMessage"></div>

            <div id="generatedReport" style="display:none; margin-top:2rem;"></div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
        // Initialize date pickers with enhanced styling
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
        
        // Format selection with enhanced interaction
        let selectedFormat = 'pdf';
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
        
        // Form validation with enhanced feedback
        function validateForm() {
            const startDate = document.getElementById('startDate').value;
            const endDate = document.getElementById('endDate').value;
            const generateBtn = document.getElementById('generateBtn');
            
            generateBtn.disabled = !(startDate && endDate && selectedFormat);
        }
        
        // Handle report generation with enhanced UI feedback
        document.getElementById('generateBtn').addEventListener('click', function() {
            const startDate = document.getElementById('startDate').value;
            const endDate = document.getElementById('endDate').value;
            const generateBtn = this;
            const loadingSpinner = generateBtn.querySelector('.loading-spinner');
            const downloadLink = document.getElementById('downloadLink');
            const errorMessage = document.getElementById('errorMessage');
            const successMessage = document.getElementById('successMessage');

            // Show loading state
            generateBtn.disabled = true;
            loadingSpinner.style.display = 'inline-block';
            downloadLink.style.display = 'none';
            errorMessage.style.display = 'none';
            successMessage.style.display = 'none';

            // Prepare form data
            const formData = new FormData();
            formData.append('format', selectedFormat);
            formData.append('start_date', startDate);
            formData.append('end_date', endDate);

            // Send request to generate report
            const endpoint = selectedFormat === 'pdf' ? 
                '../function/generate_pdf_report.php' : 
                '../function/generate_txt_report.php';

            fetch(endpoint, {
                method: 'POST',
                body: formData
            })
            .then(async response => {
                if (!response.ok) {
                    // Try to parse as JSON, but fallback to text (HTML error page)
                    let errorText = await response.text();
                    try {
                        const errorData = JSON.parse(errorText);
                        throw new Error(errorData.message || 'Network response was not ok');
                    } catch (e) {
                        // Not JSON, probably HTML error page
                        throw new Error('Server error: ' + errorText.substring(0, 100) + '...');
                    }
                }

                const contentType = response.headers.get('content-type');
                const responseClone = response.clone();

                try {
                    if (contentType && contentType.includes('application/json')) {
                        return await response.json();
                    } else if (contentType && contentType.includes('text/plain')) {
                        return await response.text();
                    } else if (contentType && (
                        contentType.includes('application/pdf') ||
                        contentType.includes('application/vnd.openxmlformats-officedocument.wordprocessingml.document') ||
                        contentType.includes('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
                    )) {
                        return await response.blob();
                    } else {
                        // Fallback handling
                        const text = await response.text();
                        try {
                            return JSON.parse(text);
                        } catch (e) {
                            return await responseClone.blob();
                        }
                    }
                } catch (error) {
                    return await responseClone.blob();
                }
            })
            .then(data => {
                if (data instanceof Blob && selectedFormat === 'pdf') {
                    // Download as before
                    const url = window.URL.createObjectURL(data);
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = `election_report_${new Date().toISOString().slice(0,10)}.pdf`;
                    document.body.appendChild(a);
                    a.click();
                    a.remove();

                    successMessage.textContent = 'PDF report downloaded successfully';
                    successMessage.style.display = 'block';

                    // Show PDF in iframe for printing
                    const generatedReport = document.getElementById('generatedReport');
                    generatedReport.style.display = 'block';
                    generatedReport.innerHTML = `<iframe id="pdfFrame" src="${url}" width="100%" height="600px" style="border:none;"></iframe>
                        <div class="text-center mt-2">
                            <button class="btn btn-primary" id="printPdfBtn">Print PDF</button>
                        </div>`;
                        

                    // Add print and cleanup logic
                    document.getElementById('printPdfBtn').onclick = function() {
                        const frame = document.getElementById('pdfFrame');
                        frame.contentWindow.focus();
                        frame.contentWindow.print();
                        // Optionally, revoke the URL after a delay
                        setTimeout(() => {
                            window.URL.revokeObjectURL(url);
                        }, 10000); // 10 seconds after print
                    };
                } else if (typeof data === 'string') {
                    // Handle text file download
                    const blob = new Blob([data], { type: 'text/plain' });
                    const url = window.URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = `election_report_${new Date().toISOString().slice(0,10)}.txt`;
                    document.body.appendChild(a);
                    a.click();
                    window.URL.revokeObjectURL(url);
                    a.remove();
                    
                    successMessage.textContent = 'Text report downloaded successfully';
                    successMessage.style.display = 'block';

                    const generatedReport = document.getElementById('generatedReport');
                    if (selectedFormat === 'txt' && typeof data === 'string') {
                        generatedReport.style.display = 'block';
                        generatedReport.innerHTML = `<pre style="white-space: pre-wrap; font-family: inherit;">${data}</pre>`;
                    }
                } else if (data.success) {
                    successMessage.textContent = data.message;
                    successMessage.style.display = 'block';
                } else {
                    throw new Error(data.message || 'Failed to generate report');
                }
            })
            .catch(error => {
                errorMessage.textContent = error.message;
                errorMessage.style.display = 'block';
            })
            .finally(() => {
                generateBtn.disabled = false;
                loadingSpinner.style.display = 'none';
            });
        });

        // Enhanced back button transition handler
        function handleBackClick(event) {
            event.preventDefault();
            const link = event.currentTarget.href;
            
            // Add exit animation to all elements
            document.body.classList.add('page-exit');
            document.querySelector('.container').style.animation = 'fadeOut 0.5s cubic-bezier(0.4, 0, 0.2, 1) forwards';
            document.querySelector('.report-card').style.animation = 'scaleOut 0.5s cubic-bezier(0.4, 0, 0.2, 1) forwards';
            
            // Add scaleOut animation to the style
            const style = document.createElement('style');
            style.textContent = `
                @keyframes fadeOut {
                    0% {
                        opacity: 1;
                        transform: translateX(0);
                    }
                    100% {
                        opacity: 0;
                        transform: translateX(30px);
                    }
                }
                @keyframes scaleOut {
                    0% {
                        opacity: 1;
                        transform: scale(1);
                    }
                    100% {
                        opacity: 0;
                        transform: scale(0.95);
                    }
                }
            `;
            document.head.appendChild(style);
            
            // Wait for animations to complete before navigating
            setTimeout(() => {
                window.location.href = link;
            }, 500);
        }

        // Add smooth scroll behavior
        document.documentElement.style.scrollBehavior = 'smooth';

        // Add intersection observer for fade-in animations
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }
            });
        }, {
            threshold: 0.1
        });

        // Observe all sections for fade-in
        document.querySelectorAll('.date-range-container, .format-option').forEach(section => {
            section.style.opacity = '0';
            section.style.transform = 'translateY(20px)';
            section.style.transition = 'all 0.6s cubic-bezier(0.4, 0, 0.2, 1)';
            observer.observe(section);
        });

        // Print functionality
        document.getElementById('printBtn').addEventListener('click', function() {
            const reportContent = document.getElementById('generatedReport');
            if (!reportContent || !reportContent.innerHTML.trim()) {
                alert('Please generate a report first.');
                return;
            }
            const printWindow = window.open('', '', 'width=900,height=700');
            printWindow.document.write('<html><head><title>Print Report</title>');
            printWindow.document.write('<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">');
            printWindow.document.write('<style>body{background:#fff;}</style>');
            printWindow.document.write('</head><body>');
            printWindow.document.write(reportContent.outerHTML);
            printWindow.document.write('</body></html>');
            printWindow.document.close();
            printWindow.focus();
            setTimeout(() => { printWindow.print(); printWindow.close(); }, 500);
        });
    </script>
</body>
</html> 