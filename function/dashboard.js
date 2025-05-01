// Function to fetch and update total candidates
function updateTotalCandidates() {
    fetch('../src/get_total_candidates.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('totalCandidates').textContent = data.total;
            }
        })
        .catch(error => console.error('Error fetching total candidates:', error));
}

// Update dashboard data when page loads
document.addEventListener('DOMContentLoaded', function() {
    updateTotalCandidates();
    // Add other dashboard update functions here
});

// Update data periodically (every 30 seconds)
setInterval(function() {
    updateTotalCandidates();
    // Add other periodic update functions here
}, 30000); 