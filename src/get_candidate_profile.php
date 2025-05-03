<?php
require_once 'connection.php';

// Fetch the candidate details based on candidate_id (passed via GET or POST)
if (isset($_GET['candidate_id'])) {
    $candidate_id = $_GET['candidate_id'];

    // Fetch candidate details including photo
    $stmt = $con->prepare("SELECT candidate_id, name, department, position, platform, photo FROM candidate WHERE candidate_id = ?");
    $stmt->bind_param("i", $candidate_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $candidate = $result->fetch_assoc();
    $stmt->close();

    if ($candidate) {
        // If photo exists, use the photo path, else fallback to a default image
        $photoPath = !empty($candidate['photo']) && file_exists($candidate['photo'])
            ? $candidate['photo']
            : 'path/to/default/photo.png';  // Default photo

        // Return candidate data as a JavaScript object
        echo "
        <script>
            var candidate = " . json_encode($candidate) . ";
            candidate.photoPath = '$photoPath';
            showCandidateProfile(candidate);
        </script>";
    } else {
        echo "<script>alert('Candidate not found.');</script>";
    }
} else {
    echo "<script>alert('Candidate ID not provided.');</script>";
}
exit;
?>
