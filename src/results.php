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

// Function to check election timeline
function checkElectionTimeline($con) {
    $stmt = $con->prepare("SELECT results_date FROM election_dates WHERE id = 1");
    $stmt->execute();
    $result = $stmt->get_result();
    $election_dates = $result->fetch_assoc();
    $stmt->close();

    if (!$election_dates) {
        return false;
    }

    $current_time = new DateTime();
    $results_date = new DateTime($election_dates['results_date']);

    return $current_time >= $results_date;
}

// Get election dates
$stmt = $con->prepare("SELECT start_date, end_date, results_date FROM election_dates WHERE id = 1");
$stmt->execute();
$result = $stmt->get_result();
$election_dates = $result->fetch_assoc();
$stmt->close();

$can_view_results = checkElectionTimeline($con);


// Define position order for each department
$positionOrder = [
    'USG' => [
        'President',
        'Vice President',
        'General Secretary',
        'Associate Secretary',
        'Treasurer',
        'Auditor',
        'Public Information Officer',
        'BSIT Representative',
        'BTLED Representative',
        'BFPT Representative'
    ],
    'PAFE' => [
        'President',
        'Vice President',
        'General Secretary',
        'Associate Secretary',
        'Treasurer',
        'Auditor',
        'Public Information Officer'
    ],
    'SITE' => [
        'President',
        'Vice President',
        'General Secretary',
        'Associate Secretary',
        'Treasurer',
        'Auditor',
        'Public Information Officer'
    ],
    'AFPROTECHS' => [
        'President',
        'Vice President',
        'General Secretary',
        'Associate Secretary',
        'Treasurer',
        'Auditor',
        'Public Information Officer'
    ]
];

// Fetch all candidates and their votes
$query = "SELECT c.department, c.position, c.name, c.candidate_id, c.photo,
          COALESCE(SUM(r.votes), 0) as votes
          FROM candidate c
          LEFT JOIN result r ON c.candidate_id = r.candidate_id 
          WHERE c.department IN ('USG', 'PAFE', 'SITE', 'AFPROTECHS')
          GROUP BY c.department, c.position, c.candidate_id, c.name, c.photo
          ORDER BY FIELD(c.department, 'USG', 'PAFE', 'SITE', 'AFPROTECHS'), 
                   c.position, 
                   votes DESC";

$result = mysqli_query($con, $query);
$results = array();

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        // Format the photo path
        $photo = !empty($row['photo']) ? $row['photo'] : '../img/icon.png';
        
        $results[] = array(
            'department' => $row['department'],
            'position' => $row['position'],
            'name' => $row['name'],
            'candidate_id' => $row['candidate_id'],
            'photo' => $photo,
            'votes' => (int)$row['votes']
        );
    }
}

// Initialize total votes
$totalVotes = 0;
foreach ($results as $result) {
    $totalVotes += $result['votes'];
}
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
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
            margin-bottom: 0.75rem;
            overflow: hidden;
        }
        .result-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .department-header {
            background: linear-gradient(135deg, #2563eb 0%, #1e40af 100%);
            color: white;
            padding: 1rem;
            margin-bottom: 1rem;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(37,99,235,0.15);
        }
        .position-header {
            background: #f8fafc;
            padding: 0.75rem 1rem;
            margin: 1rem 0;
            border-radius: 6px;
            border-left: 4px solid #2563eb;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .candidate-card {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 0.75rem;
            margin-bottom: 0.75rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        .candidate-photo {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            object-fit: cover;
            border: 1px solid #e5e7eb;
        }
        .candidate-info {
            flex: 1;
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        .candidate-details {
            flex: 1;
        }
        .candidate-name {
            font-size: 1rem;
            font-weight: 600;
            margin-bottom: 0.25rem;
        }
        .vote-info {
            font-size: 0.875rem;
            color: #6b7280;
        }
        .progress {
            height: 6px;
            margin-top: 0.35rem;
        }
        .progress-bar {
            background-color: #2563eb;
        }
        .department-section {
            margin-bottom: 1.5rem;
        }
        .department-title {
            font-size: 1.25rem;
            margin-bottom: 1rem;
        }
        .position-title {
            font-size: 1rem;
            margin-bottom: 0.5rem;
        }
        .candidates-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 0.75rem;
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
        .chart-title {
            font-size: 1rem;
            font-weight: 600;
            margin-bottom: 0.75rem;
            color: #1f2937;
        }
        .chart-wrapper {
            position: relative;
            height: 300px;
            margin-bottom: 1rem;
        }
        .chart-legend {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 1rem;
            margin-top: 1rem;
        }
        .legend-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.875rem;
            color: #4b5563;
        }
        .legend-color {
            width: 12px;
            height: 12px;
            border-radius: 2px;
        }
        .results-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        @media (max-width: 768px) {
            .candidates-list {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
            .candidate-card {
                padding: 1rem;
                margin-bottom: 1rem;
                border-radius: 12px;
            }
            .candidate-photo {
                width: 56px;
                height: 56px;
            }
            .candidate-info {
                gap: 1rem;
            }
            .candidate-name {
                font-size: 1.15rem;
                margin-bottom: 0.3rem;
            }
            .vote-info {
                font-size: 1.05rem;
            }
            .progress {
                height: 7px;
                margin-top: 0.4rem;
            }
        }
        @media (max-width: 480px) {
            .candidate-card {
                padding: 0.85rem;
                border-radius: 10px;
            }
            .candidate-photo {
                width: 48px;
                height: 48px;
            }
            .candidate-name {
                font-size: 1rem;
            }
            .vote-info {
                font-size: 0.95rem;
            }
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
        .backB{
            margin-left: 20px;
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
        .candidate-card {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            padding: 0.5rem;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .candidate-card.text-muted {
            opacity: 0.7;
        }
        .candidate-photo {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            border: 1px solid #e5e7eb;
        }
        .candidate-info {
            flex: 1;
        }
        .candidate-name {
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 0.5rem;
        }
        .candidate-votes {
            font-size: 0.875rem;
        }
        .vote-count, .vote-percentage {
            font-weight: 500;
        }
        .progress {
            background-color: #f3f4f6;
            border-radius: 3px;
        }
        .progress-bar {
            transition: width 0.6s ease;
        }
        .department-section {
            background: #fff;
            border-radius: 16px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }
        .position-section {
            background: #f8fafc;
            padding: 1.5rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
        }
        .position-subtitle {
            font-size: 0.875rem;
            color: #6b7280;
        }
        .chart-container {
            background: #fff;
            border-radius: 16px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }
        .chart-title {
            color: #1f2937;
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 1rem;
            text-align: center;
        }
        .chart-wrapper {
            position: relative;
            height: 300px;
            margin-bottom: 1rem;
        }
        .chart-legend {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 1rem;
            margin-top: 1rem;
        }
        .legend-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.875rem;
            color: #4b5563;
        }
        .legend-color {
            width: 12px;
            height: 12px;
            border-radius: 2px;
        }
        .results-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        @media (max-width: 768px) {
            .chart-wrapper {
                height: 250px;
            }
        }
        /* Small mobile devices */
        @media (max-width: 480px) {
            .candidate-card {
                padding: 0.5rem;
            }
            
            .candidate-photo {
                width: 38px;
                height: 38px;
            }
            
            .candidate-name {
                font-size: 0.9rem;
            }
            
            .vote-info {
                font-size: 0.8rem;
            }
        }
    </style>
</head>
<body>
    <audio src="assets/resultBG.mp3" autoplay loop hidden></audio>
    <!-- Navbar -->
    
    <nav class="navbar navbar-dark bg-primary shadow-sm" style="background: linear-gradient(90deg, rgb(26, 57, 119), rgb(72, 74, 80)); height: 60px;">
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
            <p class="mt-3 text-muted">
                Results will be available after 
                <?php echo date('F d, Y h:i A', strtotime($election_dates['results_date'])); ?>
            </p>
        </div>
    <?php endif; ?>
</div>

    </div>

    <!-- Candidate Profile Modal -->
    <div class="modal fade" id="candidateProfileModal" tabindex="-1" aria-labelledby="candidateProfileModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content p-3">
          <div class="modal-header d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center gap-2">
              <img id="candidateProfilePhoto" class="rounded-circle" alt="Candidate Photo"
                   style="width: 56px; height: 56px; object-fit: cover; border: 2px solid #2563eb;">
              <h5 class="modal-title mb-0" id="candidateProfileModalLabel">Candidate Name</h5>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <div id="candidateProfileCard" class="card shadow rounded-4 border-0">
              <div class="card-body">
                <p class="mb-2"><strong>Department:</strong> <span id="profileDept"></span></p>
                <p class="mb-2"><strong>Position:</strong> <span id="profilePosition"></span></p>
                <div>
                  <strong>Platform:</strong>
                  <p id="profilePlatform" class="mt-1 mb-0"></p>
                </div>
              </div>
            </div>
          </div>
        </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Initialize results data from PHP
        const initialResults = <?php echo json_encode($results); ?>;
        const canViewResults = <?php echo json_encode($can_view_results); ?>;
        const hasVoted = <?php echo json_encode($has_voted); ?>;

        function fetchAndDisplayResults() {
            <?php if ($can_view_results): ?>
            // Use the initial results data
            if (initialResults && initialResults.length > 0) {
                displayResults(initialResults);
                createPieCharts(initialResults);
                    } else {
                const container = document.getElementById('resultsContainer');
                container.innerHTML = `
                    <div class="text-center py-5">
                        <i class="bi bi-exclamation-circle text-warning" style="font-size: 3rem;"></i>
                        <p class="mt-3">No results available yet.</p>
                            </div>
                        `;
                    }
            <?php else: ?>
            const container = document.getElementById('resultsContainer');
            container.innerHTML = `
                <div class="text-center py-5">
                    <i class="bi bi-clock text-primary" style="font-size: 3rem;"></i>
                    <p class="mt-3">Results will be available after the election period ends.</p>
                </div>
            `;
            <?php endif; ?>
        }

        // Function to display results
        function displayResults(results) {
            const container = document.getElementById('resultsContainer');
            const departments = ['USG', 'PAFE', 'SITE', 'AFPROTECHS'];
            let html = '';

            departments.forEach(department => {
                const departmentResults = results.filter(r => r.department === department);
                if (departmentResults.length > 0) {
                    html += `
                        <div class="department-section">
                            <h3 class="department-title mb-4">${department}</h3>
                            ${displayDepartmentResults(departmentResults)}
                        </div>
                    `;
                }
            });

            container.innerHTML = html;
        }

        // Function to display department results
        function displayDepartmentResults(results) {
            const positions = [...new Set(results.map(r => r.position))];
            let html = '';

            positions.forEach(position => {
                const positionResults = results.filter(r => r.position === position);
                const totalVotes = positionResults.reduce((sum, r) => sum + r.votes, 0);

                html += `
                    <div class="position-section">
                        <h4 class="position-title">${position}</h4>
                        <div class="candidates-list">
                            ${positionResults.map(candidate => {
                                const percentage = totalVotes > 0 ? ((candidate.votes / totalVotes) * 100).toFixed(1) : 0;
                                return `
                                    <div class="candidate-card" onclick="showCandidateProfile(${candidate.candidate_id})">
                                        <div class="candidate-info">
                                            <img src="${candidate.photo}" alt="${candidate.name}" class="candidate-photo">
                                            <div class="candidate-details">
                                                <h5 class="candidate-name">${candidate.name}</h5>
                                                <div class="vote-info">
                                                    <span class="vote-count">${candidate.votes} votes</span>
                                                    <span class="vote-percentage">(${percentage}%)</span>
                                                </div>
                                                <div class="progress mt-2">
                                                    <div class="progress-bar" role="progressbar" 
                                                         style="width: ${percentage}%" 
                                                         aria-valuenow="${percentage}" 
                                                         aria-valuemin="0" 
                                                         aria-valuemax="100">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                `;
                            }).join('')}
                        </div>
                        </div>
                    `;
                });

            return html;
        }

        // Function to create pie charts
        function createPieCharts(results) {
            // Overall Results Chart
            const overallCtx = document.getElementById('overallResultsChart').getContext('2d');
            const departmentTotals = {};
            
            results.forEach(result => {
                if (!departmentTotals[result.department]) {
                    departmentTotals[result.department] = 0;
                }
                departmentTotals[result.department] += result.votes;
            });

            new Chart(overallCtx, {
                type: 'pie',
                data: {
                    labels: Object.keys(departmentTotals),
                    datasets: [{
                        data: Object.values(departmentTotals),
                        backgroundColor: [
                            '#2563eb',
                            '#7c3aed',
                            '#db2777',
                            '#ea580c'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        },
                        title: {
                            display: true,
                            text: 'Overall Vote Distribution'
                        }
                    }
                }
            });

            // Department Results Chart
            const deptCtx = document.getElementById('departmentResultsChart').getContext('2d');
            const positionTotals = {};
            
            results.forEach(result => {
                if (!positionTotals[result.position]) {
                    positionTotals[result.position] = 0;
                }
                positionTotals[result.position] += result.votes;
            });

            new Chart(deptCtx, {
                type: 'bar',
                data: {
                    labels: Object.keys(positionTotals),
                    datasets: [{
                        label: 'Total Votes',
                        data: Object.values(positionTotals),
                        backgroundColor: '#2563eb'
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            display: false
                        },
                        title: {
                            display: true,
                            text: 'Votes by Position'
                            }
                        },
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        }

        // Call the function when the page loads
        document.addEventListener('DOMContentLoaded', fetchAndDisplayResults);

        function showCandidateProfile(candidateId) {
            fetch(`get_candidate.php?candidate_id=${candidateId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.candidate) {
                        document.getElementById('candidateProfileModalLabel').textContent = data.candidate.name || 'Candidate Profile';
                        document.getElementById('profileDept').textContent = data.candidate.department || 'N/A';
                        document.getElementById('profilePosition').textContent = data.candidate.position || 'N/A';
                        document.getElementById('profilePlatform').textContent = data.candidate.platform || 'N/A';
                        document.getElementById('candidateProfilePhoto').src = data.candidate.photo || '../img/icon.png';
                        const profileModal = new bootstrap.Modal(document.getElementById('candidateProfileModal'));
                        profileModal.show();
                    } else {
                        alert('Candidate profile not found.');
                    }
                })
                .catch(() => {
                    alert('Error loading candidate profile.');
                });
        }
    </script>
</body>
</html> 