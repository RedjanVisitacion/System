<?php
require_once 'check_session.php';
require_once 'connection.php';


// ✅ Remove Candidate Logic
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['candidate_id'])) {
  header('Content-Type: application/json');
  
  $candidate_id = trim($_POST['candidate_id']);

  if (empty($candidate_id)) {
      echo json_encode(['success' => false, 'message' => 'No candidate selected.']);
      exit;
  }

  // Ensure candidate_id is numeric to avoid injection
  if (!ctype_digit($candidate_id)) {
      echo json_encode(['success' => false, 'message' => 'Invalid candidate ID.']);
      exit;
  }

  // Prepare and execute the delete query
  $stmt = $con->prepare("DELETE FROM candidate WHERE candidate_id = ?");
  $stmt->bind_param("i", $candidate_id);

  if ($stmt->execute()) {
      if ($stmt->affected_rows > 0) {
          echo json_encode(['success' => true, 'message' => 'Candidate removed successfully.']);
      } else {
          echo json_encode(['success' => false, 'message' => 'Candidate not found or already removed.']);
      }
  } else {
      echo json_encode(['success' => false, 'message' => 'Failed to remove candidate.']);
  }

  $stmt->close();
  exit;
}

?>
