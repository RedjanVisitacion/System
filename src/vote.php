<?php
require_once 'check_session.php';
require_once 'connection.php';

// Check if user is a student
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'student') {
    header('Location: dashboard_student.php');
    exit;
}

// Fetch user's profile picture and full name
$user_id = $_SESSION['user_id'];
$stmt = $con->prepare("SELECT profile_picture, full_name FROM user_profile WHERE user_id = ?");
$stmt->bind_param("s", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user_profile = $result->fetch_assoc();
$stmt->close();

$profile_picture = !empty($user_profile['profile_picture']) && file_exists('../uploads/profile_pictures/' . $user_profile['profile_picture'])
    ? '../uploads/profile_pictures/' . htmlspecialchars($user_profile['profile_picture'])
    : null;

// Check if user has already voted
$stmt = $con->prepare("SELECT COUNT(*) as vote_count FROM vote WHERE user_id = ? AND vote_status = 'Voted'");
$stmt->bind_param("s", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$vote_count = $result->fetch_assoc()['vote_count'];
$stmt->close();

$has_voted = $vote_count > 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Cast Vote - Electoral Commission</title>
    <link rel="icon" href="../img/icon.png"/>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&family=Libre+Baskerville:wght@400;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: #f6fafd;
            min-height: 100vh;
        }
        .navbar {
            background: #2563eb;
            height: 60px;
        }
        .vote-container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 2rem;
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 24px rgba(37, 99, 235, 0.07);
        }
        .position-section {
            margin-bottom: 2rem;
            padding: 1.5rem;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
        }
        .position-title {
            color: #2563eb;
            font-weight: 600;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #e5e7eb;
        }
        .candidate-card {
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 1rem;
            margin-bottom: 1rem;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        .candidate-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.1);
        }
        .candidate-card.selected {
            border-color: #2563eb;
            background: #f0f7ff;
        }
        .candidate-photo {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #e5e7eb;
        }
        .candidate-info {
            margin-left: 1rem;
        }
        .candidate-name {
            font-weight: 600;
            margin-bottom: 0.25rem;
        }
        .candidate-department {
            color: #6b7280;
            font-size: 0.875rem;
        }
        .submit-vote-btn {
            background: #2563eb;
            color: white;
            border: none;
            padding: 0.75rem 2rem;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        .submit-vote-btn:hover {
            background: #1d4ed8;
            transform: translateY(-1px);
        }
        .submit-vote-btn:disabled {
            background: #93c5fd;
            cursor: not-allowed;
        }
        .alert {
            border-radius: 12px;
            padding: 1rem;
            margin-bottom: 1.5rem;
        }
        .election-status {
            padding: 1rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            text-align: center;
        }
        .election-status.not-started {
            background: #fef3c7;
            color: #92400e;
        }
        .election-status.ended {
            background: #fee2e2;
            color: #991b1b;
        }
        .election-status.active {
            background: #dcfce7;
            color: #166534;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg position-relative">
        <div class="container-fluid px-4">
            <div class="d-flex align-items-center">
                <img src="../img/icon.png" alt="Electoral Commission Logo" class="me-3" style="width:44px; height:44px; background:#fff; border-radius:50%;">
                <span class="navbar-brand mb-0 h1 text-white">Cast Vote</span>
            </div>
            <div class="d-flex align-items-center">
                <a href="dashboard_student.php" class="btn btn-outline-light me-2">
                    <i class="bi bi-arrow-left"></i> Back
                </a>
                <div class="dropdown">
                    <a href="#" class="btn btn-outline-light rounded-pill d-flex align-items-center" role="button" data-bs-toggle="dropdown">
                        <?php if ($profile_picture): ?>
                            <img src="<?php echo $profile_picture; ?>" alt="Profile Picture" style="width: 32px; height: 32px; border-radius: 50%; object-fit: cover; margin-right: 8px;">
                        <?php else: ?>
                            <i class="bi bi-person-circle me-2"></i>
                        <?php endif; ?>
                        <?php echo htmlspecialchars($user_profile['full_name'] ?? ''); ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="profile.php"><i class="bi bi-person me-2"></i>Profile</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <div class="container py-4">
        <div class="vote-container">
            <?php if ($has_voted): ?>
                <div class="alert alert-success">
                    <i class="bi bi-check-circle me-2"></i>
                    You have already cast your vote. Thank you for participating!
                </div>
            <?php else: ?>
                <div id="electionStatus" class="election-status">
                    <i class="bi bi-clock me-2"></i>
                    <span>Checking election status...</span>
                </div>

                <form id="voteForm" class="d-none">
                    <div id="candidatesContainer">
                        <!-- Candidates will be loaded here -->
                    </div>

                    <div class="text-center mt-4">
                        <button type="submit" class="submit-vote-btn" disabled>
                            Submit Vote
                        </button>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Check election status
        function checkElectionStatus() {
            fetch('get_election_dates.php')
                .then(response => response.json())
                .then(data => {
                    const now = new Date();
                    const startDate = new Date(data.start_date);
                    const endDate = new Date(data.end_date);
                    const statusEl = document.getElementById('electionStatus');
                    const voteForm = document.getElementById('voteForm');

                    if (now < startDate) {
                        statusEl.className = 'election-status not-started';
                        statusEl.innerHTML = '<i class="bi bi-clock me-2"></i>Voting has not started yet.';
                        voteForm.classList.add('d-none');
                    } else if (now > endDate) {
                        statusEl.className = 'election-status ended';
                        statusEl.innerHTML = '<i class="bi bi-x-circle me-2"></i>Voting period has ended.';
                        voteForm.classList.add('d-none');
                    } else {
                        statusEl.className = 'election-status active';
                        statusEl.innerHTML = '<i class="bi bi-check-circle me-2"></i>Voting is currently active.';
                        voteForm.classList.remove('d-none');
                        loadCandidates();
                    }
                })
                .catch(error => {
                    console.error('Error checking election status:', error);
                    document.getElementById('electionStatus').innerHTML = 
                        '<i class="bi bi-exclamation-circle me-2"></i>Error checking election status.';
                });
        }

        // Load candidates
        function loadCandidates() {
            fetch('fetch_candidates.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success && Array.isArray(data.candidates)) {
                        const container = document.getElementById('candidatesContainer');
                        const positions = [...new Set(data.candidates.map(c => c.position))];
                        
                        positions.forEach(position => {
                            const positionCandidates = data.candidates.filter(c => c.position === position);
                            
                            const section = document.createElement('div');
                            section.className = 'position-section';
                            section.innerHTML = `
                                <h3 class="position-title">${position}</h3>
                                <div class="candidates-list">
                                    ${positionCandidates.map(candidate => `
                                        <div class="candidate-card d-flex align-items-center" 
                                             onclick="selectCandidate(this, '${position}', ${candidate.candidate_id})">
                                            <img src="${candidate.photo || '../img/default-avatar.png'}" 
                                                 alt="${candidate.name}" 
                                                 class="candidate-photo">
                                            <div class="candidate-info">
                                                <div class="candidate-name">${candidate.name}</div>
                                                <div class="candidate-department">${candidate.department}</div>
                                            </div>
                                        </div>
                                    `).join('')}
                                </div>
                            `;
                            container.appendChild(section);
                        });
                    }
                })
                .catch(error => {
                    console.error('Error loading candidates:', error);
                });
        }

        // Handle candidate selection
        const selectedCandidates = new Map();

        function selectCandidate(element, position, candidateId) {
            const positionSection = element.closest('.position-section');
            const allCards = positionSection.querySelectorAll('.candidate-card');
            
            // Remove selected class from all cards in this position
            allCards.forEach(card => card.classList.remove('selected'));
            
            // Add selected class to clicked card
            element.classList.add('selected');
            
            // Store the selection
            selectedCandidates.set(position, candidateId);
            
            // Enable submit button if all positions have selections
            const submitBtn = document.querySelector('.submit-vote-btn');
            const allPositions = document.querySelectorAll('.position-section');
            submitBtn.disabled = selectedCandidates.size !== allPositions.length;
        }

        // Handle form submission
        document.getElementById('voteForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const votes = Array.from(selectedCandidates.entries()).map(([position, candidateId]) => ({
                position,
                candidate_id: candidateId
            }));

            fetch('vote_status.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    votes: votes
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Your vote has been recorded successfully!');
                    window.location.href = 'dashboard_student.php';
                } else {
                    alert(data.message || 'Error recording your vote. Please try again.');
                }
            })
            .catch(error => {
                console.error('Error submitting vote:', error);
                alert('Error submitting your vote. Please try again.');
            });
        });

        // Initial check
        checkElectionStatus();
        // Check every minute
        setInterval(checkElectionStatus, 60000);
    </script>
</body>
</html> 