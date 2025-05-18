<?php

include_once 'connection.php';

function fetchCandidatesSorted($conn) {
  // Define custom order for departments
  $query = "
    SELECT * FROM elecom_candidate
    ORDER BY 
      FIELD(department, 'USG', 'BSIT', 'BTLED', 'BFPT'), 
      position ASC, 
      name ASC
  ";

  $result = mysqli_query($conn, $query);
  $candidates = [];

  if ($result && mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
      $candidates[] = $row;
    }
  }

  return $candidates;
}
?>
