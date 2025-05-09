<?php
require_once 'check_session.php';
require_once 'connection.php';

// Fetch user's profile picture and full name
$user_id = $_SESSION['user_id'];
$stmt = $con->prepare("SELECT profile_picture, full_name FROM user_profile WHERE user_id = ?");
$stmt->bind_param("s", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user_profile = $result->fetch_assoc();
$stmt->close();

// Set profile picture path
$profile_picture = !empty($user_profile['profile_picture']) 
    ? '../uploads/profile_pictures/' . htmlspecialchars($user_profile['profile_picture'])
    : '../img/icon.png';

// Check if user has already voted
$stmt = $con->prepare("SELECT COUNT(*) as vote_count FROM vote WHERE user_id = ? AND vote_status = 'Voted'");
$stmt->bind_param("s", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$vote_count = $result->fetch_assoc()['vote_count'];
$stmt->close();

$has_voted = $vote_count > 0;

// Query to get results with candidate information
$query = "SELECT r.department, r.position, c.name, c.candidate_id, 
          SUM(r.votes) as total_votes, MAX(r.published_at) as published_at
          FROM result r 
          JOIN candidate c ON r.candidate_id = c.candidate_id 
          GROUP BY r.department, r.position, c.candidate_id, c.name
          ORDER BY r.department, r.position, total_votes DESC";

$result = mysqli_query($con, $query);

// Check if query was successful
if (!$result) {
    die("Query failed: " . mysqli_error($con));
}

function checkElectionTimeline($con) {
    $stmt = $con->prepare("SELECT start_date, end_date, results_date FROM election_dates WHERE id = 1");
    $stmt->execute();
    $result = $stmt->get_result();
    $election_dates = $result->fetch_assoc();
    $stmt->close();

    if (!$election_dates) {
        return false;
    }

    $current_time = new DateTime();
    $end_date = new DateTime($election_dates['end_date']);
    $results_date = new DateTime($election_dates['results_date']);

    // Can view results only if:
    // 1. Current time is after end date (voting has ended)
    // 2. Current time is after results date (results are released)
    return ($current_time >= $end_date) && ($current_time >= $results_date);
}

// Get election dates for display
$stmt = $con->prepare("SELECT start_date, end_date, results_date FROM election_dates WHERE id = 1");
$stmt->execute();
$result = $stmt->get_result();
$election_dates = $result->fetch_assoc();
$stmt->close();

$can_view_results = checkElectionTimeline($con);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="icon" href="../img/icon.png"/>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Election Results</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: #f6fafd;
            color: #1f2937;
        }
        .navbar {
            background: linear-gradient(135deg, #2563eb 0%, #1e40af 100%);
            height: 60px;
            box-shadow: 0 2px 8px rgba(37,99,235,0.15);
        }
        .result-card {
            background: #fff;
            border: none;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
            margin-bottom: 1rem;
            overflow: hidden;
        }
        .result-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .department-header {
            background: linear-gradient(135deg, #2563eb 0%, #1e40af 100%);
            color: white;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(37,99,235,0.15);
        }
        .position-header {
            background: #f8fafc;
            padding: 1rem 1.5rem;
            margin: 1.5rem 0;
            border-radius: 8px;
            border-left: 4px solid #2563eb;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .votes-badge {
            background: #e0e7ff;
            color: #2563eb;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.9rem;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        .votes-badge i {
            font-size: 1rem;
        }
        .published-date {
            color: #64748b;
            font-size: 0.85rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .voted-message {
            background: #dcfce7;
            border: 1px solid #86efac;
            color: #166534;
            padding: 1.25rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            box-shadow: 0 2px 8px rgba(22,101,52,0.1);
        }
        .voted-message i {
            font-size: 2rem;
            color: #16a34a;
        }
        .voted-message-content h5 {
            margin: 0;
            font-weight: 600;
            color: #166534;
        }
        .voted-message-content p {
            margin: 0.25rem 0 0;
            font-size: 0.9rem;
            color: #15803d;
        }
        .candidate-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        .candidate-photo {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #e5e7eb;
        }
        .candidate-details {
            flex: 1;
        }
        .candidate-name {
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 0.25rem;
        }
        .candidate-position {
            font-size: 0.85rem;
            color: #6b7280;
        }
        .progress-container {
            margin-top: 0.75rem;
            background: #f1f5f9;
            border-radius: 8px;
            overflow: hidden;
        }
        .progress {
            height: 8px;
            background: linear-gradient(90deg, #2563eb 0%, #1e40af 100%);
            border-radius: 4px;
        }
        .total-votes {
            background: #f8fafc;
            padding: 0.75rem 1rem;
            border-radius: 8px;
            margin-top: 0.75rem;
            font-weight: 500;
            color: #2563eb;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .total-votes i {
            font-size: 1.1rem;
        }
        @media (max-width: 768px) {
            .container {
                padding: 1rem;
            }
            .department-header {
                padding: 1rem;
                margin: 1rem 0;
            }
            .position-header {
                padding: 0.75rem 1rem;
                margin: 1rem 0;
            }
            .candidate-photo {
                width: 40px;
                height: 40px;
            }
        }

        .backB{
            margin-left: 20px;
        }

        .chart-container {
            position: relative;
            height: 300px;
            margin: 20px 0;
            padding: 15px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }

        .winner-badge {
            position: absolute;
            top: -10px;
            right: -10px;
            background: #fbbf24;
            color: #92400e;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 5px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            z-index: 1;
        }

        .position-section {
            margin-bottom: 2rem;
            position: relative;
        }

        @media (max-width: 768px) {
            .chart-container {
                height: 250px;
                margin: 15px 0;
                padding: 12px;
            }
        }

        .results-container {
            padding: 2rem;
            max-width: 1200px;
            margin: 0 auto;
        }
        .results-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            padding: 2rem;
            margin-bottom: 2rem;
        }
        .position-title {
            color: #2563eb;
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #e5e7eb;
        }
        .candidate-result {
            display: flex;
            align-items: center;
            padding: 1rem;
            margin-bottom: 1rem;
            border-radius: 10px;
            background: #f8fafc;
            transition: transform 0.2s;
        }
        .candidate-result:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }
        .candidate-photo {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 1rem;
            border: 3px solid #2563eb;
        }
        .candidate-info {
            flex-grow: 1;
        }
        .candidate-name {
            font-weight: 600;
            margin-bottom: 0.25rem;
        }
        .candidate-department {
            color: #6b7280;
            font-size: 0.875rem;
        }
        .vote-count {
            font-size: 1.25rem;
            font-weight: 600;
            color: #2563eb;
            margin-left: 1rem;
        }
        .progress {
            height: 8px;
            margin-top: 0.5rem;
            background-color: #e5e7eb;
        }
        .progress-bar {
            background-color: #2563eb;
            transition: width 1s ease-in-out;
        }
        .no-results {
            text-align: center;
            padding: 2rem;
            color: #6b7280;
        }
        .back-button {
            margin-bottom: 2rem;
        }
        @media (max-width: 768px) {
            .results-container {
                padding: 1rem;
            }
            .chart-container {
                height: 300px;
            }
            .candidate-result {
                flex-direction: column;
                text-align: center;
            }
            .candidate-photo {
                margin: 0 auto 1rem auto;
            }
            .vote-count {
                margin: 1rem 0 0 0;
            }
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    
    <nav class="navbar navbar-dark bg-primary shadow-sm" style="min-height:60px; z-index: 1050;">
        <div class="backB">
            <a href="<?php echo $_SESSION['role'] === 'officer' ? 'dashboard_officer.php' : 'dashboard_student.php'; ?>" class="btn btn-outline-light rounded-pill d-flex align-items-center gap-2 px-3 py-1" style="font-weight:500;">
                <i class="bi bi-arrow-left fs-6"></i>
                <span class="fw-semibold" style="font-size:1rem;">Back</span>
            </a>
        </div>
    </nav>
    

    <div class="container py-4">
        <?php if ($has_voted): ?>
            <div class="voted-message">
                <i class="bi bi-check-circle-fill"></i>
                <div class="voted-message-content">
                    <h5>Thank you for voting!</h5>
                    <p>Your vote has been recorded successfully.</p>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <div class="results-container">
        <div class="results-card">
            <h2 class="text-center mb-4">Election Results</h2>
            
            <?php if ($can_view_results): ?>
                <!-- Pie Chart Section -->
                <div class="row">
                    <div class="col-md-6">
                        <div class="chart-container">
                            <canvas id="overallResultsChart"></canvas>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="chart-container">
                            <canvas id="departmentResultsChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Detailed Results Section -->
                <div id="resultsContainer">
                    <!-- Results will be loaded here -->
                </div>
            <?php else: ?>
                <div class="text-center py-5">
                    <i class="bi bi-clock-history fs-1 text-muted"></i>
                    <?php 
                    $current_time = new DateTime();
                    $end_date = new DateTime($election_dates['end_date']);
                    if ($current_time < $end_date): 
                    ?>
                        <p class="mt-3 text-muted">Voting is still in progress. Results will be available after the election ends.</p>
                    <?php else: ?>
                        <p class="mt-3 text-muted">Results will be available after <?php echo date('F d, Y h:i A', strtotime($election_dates['results_date'])); ?></p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Function to fetch and display results
        function fetchAndDisplayResults() {
            <?php if ($can_view_results): ?>
            fetch('get_results.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        displayResults(data.results);
                        createPieCharts(data.results);
                    } else {
                        document.getElementById('resultsContainer').innerHTML = `
                            <div class="no-results">
                                <i class="bi bi-exclamation-circle fs-1"></i>
                                <p class="mt-3">${data.message || 'No results available yet.'}</p>
                            </div>
                        `;
                    }
                })
                .catch(error => {
                    console.error('Error fetching results:', error);
                    document.getElementById('resultsContainer').innerHTML = `
                        <div class="no-results">
                            <i class="bi bi-exclamation-triangle fs-1"></i>
                            <p class="mt-3">Error loading results. Please try again later.</p>
                        </div>
                    `;
                });
            <?php endif; ?>
        }

        // Function to create pie charts
        function createPieCharts(results) {
            // Overall Results Chart
            const overallCtx = document.getElementById('overallResultsChart').getContext('2d');
            const overallData = prepareOverallChartData(results);
            new Chart(overallCtx, {
                type: 'pie',
                data: {
                    labels: overallData.labels,
                    datasets: [{
                        data: overallData.data,
                        backgroundColor: generateColors(overallData.labels.length),
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        title: {
                            display: true,
                            text: 'Overall Voting Results',
                            font: {
                                size: 16
                            }
                        },
                        legend: {
                            position: 'right'
                        }
                    }
                }
            });

            // Department Results Chart
            const deptCtx = document.getElementById('departmentResultsChart').getContext('2d');
            const deptData = prepareDepartmentChartData(results);
            new Chart(deptCtx, {
                type: 'pie',
                data: {
                    labels: deptData.labels,
                    datasets: [{
                        data: deptData.data,
                        backgroundColor: generateColors(deptData.labels.length),
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        title: {
                            display: true,
                            text: 'Results by Department',
                            font: {
                                size: 16
                            }
                        },
                        legend: {
                            position: 'right'
                        }
                    }
                }
            });
        }

        // Helper function to prepare data for overall chart
        function prepareOverallChartData(results) {
            const data = {};
            results.forEach(result => {
                if (!data[result.position]) {
                    data[result.position] = 0;
                }
                data[result.position] += result.votes;
            });

            return {
                labels: Object.keys(data),
                data: Object.values(data)
            };
        }

        // Helper function to prepare data for department chart
        function prepareDepartmentChartData(results) {
            const data = {};
            results.forEach(result => {
                if (!data[result.department]) {
                    data[result.department] = 0;
                }
                data[result.department] += result.votes;
            });

            return {
                labels: Object.keys(data),
                data: Object.values(data)
            };
        }

        // Helper function to generate colors
        function generateColors(count) {
            const colors = [
                '#2563eb', '#3b82f6', '#60a5fa', '#93c5fd', '#bfdbfe',
                '#1d4ed8', '#1e40af', '#1e3a8a', '#172554', '#0f172a'
            ];
            return colors.slice(0, count);
        }

        // Function to display detailed results
        function displayResults(results) {
            const container = document.getElementById('resultsContainer');
            const positions = [...new Set(results.map(r => r.position))];

            container.innerHTML = positions.map(position => {
                const positionResults = results.filter(r => r.position === position);
                const totalVotes = positionResults.reduce((sum, r) => sum + r.votes, 0);

                return `
                    <div class="position-section mb-4">
                        <h3 class="position-title">${position}</h3>
                        ${positionResults.map(candidate => {
                            const percentage = totalVotes > 0 ? (candidate.votes / totalVotes * 100).toFixed(1) : 0;
                            return `
                                <div class="candidate-result">
                                    <img src="${candidate.photo || '../img/icon.png'}" 
                                         alt="${candidate.name}" 
                                         class="candidate-photo">
                                    <div class="candidate-info">
                                        <div class="candidate-name">${candidate.name}</div>
                                        <div class="candidate-department">${candidate.department}</div>
                                        <div class="progress">
                                            <div class="progress-bar" 
                                                 role="progressbar" 
                                                 style="width: ${percentage}%" 
                                                 aria-valuenow="${percentage}" 
                                                 aria-valuemin="0" 
                                                 aria-valuemax="100">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="vote-count">
                                        ${candidate.votes} votes
                                        <div class="text-muted small">${percentage}%</div>
                                    </div>
                                </div>
                            `;
                        }).join('')}
                    </div>
                `;
            }).join('');
        }

        // Load results when page loads
        document.addEventListener('DOMContentLoaded', fetchAndDisplayResults);
    </script>
</body>
</html> 