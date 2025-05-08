// Function to fetch and update total candidates
function updateTotalCandidates() {
    fetch('../src/get_total_candidates.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('totalCandidates').textContent = data.total;
            }
        })
        .catch(error => console.error('Error:', error));
}

// Function to handle reset votes
function handleResetVotes() {
    if (confirm('Are you sure you want to reset all votes? This action cannot be undone.')) {
        fetch('../src/reset_votes.php', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                alert('All votes have been reset successfully.');
                window.location.reload();
            } else {
                alert('Error: ' + (data.message || 'Failed to reset votes'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error connecting to server. Please try again.');
        });
    }
}

// Update dashboard data when page loads
document.addEventListener('DOMContentLoaded', function() {
    updateTotalCandidates();
    
    // Add event listeners for both reset buttons
    const resetBtn1 = document.getElementById('resetVotesBtn1');
    const resetBtn2 = document.getElementById('resetVotesBtn2');
    
    if (resetBtn1) {
        resetBtn1.addEventListener('click', handleResetVotes);
    }
    if (resetBtn2) {
        resetBtn2.addEventListener('click', handleResetVotes);
    }

    // Update total candidates every 30 seconds
    setInterval(updateTotalCandidates, 30000);
}); 