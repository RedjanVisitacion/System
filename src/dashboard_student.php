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
$profile_picture = isset($user_profile['profile_picture']) && !empty($user_profile['profile_picture']) 
    ? '../uploads/profile_pictures/' . htmlspecialchars($user_profile['profile_picture']) 
    : '../img/icon.png';


// Fetch and display candidate details (with photo)
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['candidate_id'])) {
  $candidate_id = $_GET['candidate_id'];

  // Fetch candidate details including photo
  $stmt = $con->prepare("SELECT name, department, position, platform, photo FROM candidate WHERE candidate_id = ?");
  $stmt->bind_param("i", $candidate_id);
  $stmt->execute();
  $result = $stmt->get_result();
  $candidate = $result->fetch_assoc();
  $stmt->close();

  if ($candidate) {
      // If photo exists, use the photo path, else fallback to default image
      $photoPath = !empty($candidate['photo']) 
          ? '../uploads/profile_pictures/' . htmlspecialchars($candidate['photo'])
          : '../img/icon.png';

      // Send the candidate data with the photo path
      echo json_encode([
          'success' => true,
          'candidate' => $candidate,
          'photo' => $photoPath
      ]);
  } else {
      echo json_encode(['success' => false, 'message' => 'Candidate not found.']);
  }
  exit;
}

// Check if user has already voted
$stmt = $con->prepare("SELECT COUNT(*) as vote_count FROM vote WHERE user_id = ? AND vote_status = 'Voted'");
$stmt->bind_param("s", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$vote_count = $result->fetch_assoc()['vote_count'];
$stmt->close();

$has_voted = $vote_count > 0;

// Check election timeline
$stmt = $con->prepare("SELECT start_date, end_date, results_date FROM election_dates WHERE id = 1");
$stmt->execute();
$result = $stmt->get_result();
$election_dates = $result->fetch_assoc();
$stmt->close();

$can_view_results = false;
if ($election_dates) {
    $current_time = new DateTime();
    $end_date = new DateTime($election_dates['end_date']);
    $results_date = new DateTime($election_dates['results_date']);
    
    // Can view results only if:
    // 1. Current time is after end date (voting has ended)
    // 2. Current time is after results date (results are released)
    $can_view_results = ($current_time >= $end_date) && ($current_time >= $results_date);
}

// Add this function at the top of the file after the require statements
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
  <meta charset="UTF-8">
  <title>Electoral Commission Dashboard</title>
  <link rel="icon" href="../img/icon.png"/>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&family=Libre+Baskerville:wght@400;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="main.css">
  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

  <!-- Bootstrap Icons (if you're using bi-list) -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">

  <meta name="viewport" content="width=device-width, initial-scale=1">
  <style>
    body {
      
      font-family: 'Poppins', 'Segoe UI', Arial, sans-serif;
      background: #f6fafd;
      background: linear-gradient(rgba(255, 255, 255, 0.7), rgba(39, 39, 41, 0.5)),
                url('../img/voteBG.gif') center/cover no-repeat fixed;
      min-height: 100vh;
      padding-top: 0;
    }
    .navbar {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      z-index: 1200;
      background: #2563eb;
      height: 60px;
    }
    .navbar.faded {
      opacity: 0.92;
      transition: opacity 0.3s;
    }
    .bg-dark {
      background: linear-gradient(135deg, #232526 0%, #2563eb 100%) !important;
    }
    .nav-link {
      transition: background 0.2s, color 0.2s;
      border-radius: 8px;
      padding: 10px 16px;
    }
    .nav-link:hover, .nav-link.active {
      background: #2563eb;
      color: #fff !important;
    }
    .mobile-profile-pic {
      display: none;
    }
    .profile-button {
      display: flex;
    }
    .profile-button a {
      transition: all 0.3s ease;
    }
    
    .profile-button a:hover {
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(255, 255, 255, 0.2);
    }
    
    .profile-button img, .mobile-profile-pic {
      transition: all 0.3s ease;
    }
    
    .profile-button:hover img {
      transform: scale(1.1);
    }
    
    .mobile-profile-container {
      position: relative;
      transition: all 0.3s ease;
    }
    
    .mobile-profile-container::after {
      content: '';
      position: absolute;
      bottom: -4px;
      left: 50%;
      width: 0;
      height: 2px;
      background: #fff;
      transition: all 0.3s ease;
      transform: translateX(-50%);
    }
    
    .mobile-profile-container:hover::after {
      width: 100%;
    }
    
    .mobile-profile-container:active .mobile-profile-pic {
      transform: scale(0.95);
    }
    
    @keyframes profilePulse {
      0% {
        box-shadow: 0 0 0 0 rgba(255, 255, 255, 0.4);
      }
      70% {
        box-shadow: 0 0 0 10px rgba(255, 255, 255, 0);
      }
      100% {
        box-shadow: 0 0 0 0 rgba(255, 255, 255, 0);
      }
    }
    
    .profile-button a:hover img,
    .mobile-profile-container:hover .mobile-profile-pic {
      animation: profilePulse 1.5s infinite;
    }

    /* Original mobile styles */
    @media (max-width: 575.98px) {
      body {
        background: #f8fafc;
        margin: 0;
        padding: 0;
        min-height: 100vh;
        overflow: hidden;
        position: fixed;
        width: 100%;
      }

      .navbar {
        position: fixed !important;
        top: 0 !important;
        left: 0;
        width: 100%;
        z-index: 1200;
        height: 56px;
        padding: 0 1rem !important;
        background: #2563eb;
        display: flex;
        align-items: center;
      }

      .main-content {
        margin: 0 !important;
        padding: 0.75rem !important;
        height: calc(100vh - 56px);
        background: #f8fafc;
        width: 100%;
        overflow-y: auto;
        position: fixed;
        top: 56px;
        left: 0;
        right: 0;
        bottom: 0;
      }

      #sidebar {
        position: fixed;
        right: -240px;
        left: auto;
        top: 56px;
        height: calc(100vh - 56px);
        z-index: 1102;
        transform: translateX(0);
        transition: right 0.3s cubic-bezier(0.4,0.2,0.2,1);
        box-shadow: -2px 0 8px rgba(0,0,0,0.10);
        width: 240px !important;
        min-width: 60px !important;
        background: linear-gradient(135deg, #232526 0%, #2563eb 100%) !important;
        padding: 1.5rem 1rem 1rem 1rem !important;
        display: flex;
        flex-direction: column;
        overflow: hidden;
      }

      #sidebar.active {
        right: 0;
        left: auto;
      }

      #sidebar .sidebar-header-container {
        flex-shrink: 0;
        margin-bottom: 1rem;
      }

      #sidebar .nav {
        flex: 1;
        overflow-y: auto;
        -webkit-overflow-scrolling: touch;
        padding-right: 0.5rem;
        margin: 0 -0.5rem;
      }

      #sidebar .nav::-webkit-scrollbar {
        width: 4px;
      }

      #sidebar .nav::-webkit-scrollbar-track {
        background: rgba(255, 255, 255, 0.1);
      }

      #sidebar .nav::-webkit-scrollbar-thumb {
        background: rgba(255, 255, 255, 0.3);
        border-radius: 2px;
      }

      .sidebar-overlay {
        position: fixed;
        top: 56px;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.5);
        z-index: 1101;
        display: none;
      }

      .sidebar-overlay.active {
        display: block;
      }

      .row.g-4 {
        margin: 0 !important;
        padding: 0 !important;
        width: 100%;
        gap: 0.75rem !important;
      }

      .col-12.col-lg-8 {
        width: 100%;
        padding: 0;
      }

      .col-12.col-md-6 {
        width: 100%;
        padding: 0;
        margin-bottom: 0.75rem;
      }

      .dashboard-card {
        background: white;
        border-radius: 16px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        padding: 1rem;
        margin: 0;
        display: flex;
        flex-direction: column;
        align-items: center;
        height: auto;
      }

      .mobile-menu-btn {
        display: block !important;
        padding: 4px !important;
        margin-right: 8px !important;
      }

      .mobile-menu-btn i {
        font-size: 1.75rem !important;
        transition: transform 0.3s ease;
      }

      .mobile-menu-btn.active i {
        transform: rotate(45deg);
      }

      .mobile-menu-btn.active i::before {
        content: '\F659' !important;
      }

      .elecom-logo {
        display: none !important;
      }

      .profile-button {
        display: none !important;
      }

      .electoral-commission-title {
        font-size: 1rem !important;
        margin: 0 !important;
        color: white !important;
        -webkit-text-fill-color: white !important;
        text-shadow: none !important;
        background: none !important;
      }
    }
    @media (min-width: 576px) {
      .mobile-menu-btn {
        display: none !important;
      }
    }
    @media (min-width: 576px) and (max-width: 767.98px) {
      .electoral-commission-title {
        font-size: 1.25rem !important;
      }
      .dashboard-card {
        padding: 1.25rem !important;
      }
      .dashboard-card .icon {
        width: 42px;
        height: 42px;
        font-size: 1.5rem;
      }
      .dashboard-card .fw-bold {
        font-size: 1.1rem !important;
      }
      .dashboard-card .fs-4 {
        font-size: 1.75rem !important;
      }
    }
    @media (max-width: 767px) {
      .main-content {
        margin-left: 0;
      }
      .row > * {
        padding-right: calc(var(--bs-gutter-x) * .25);
        padding-left: calc(var(--bs-gutter-x) * .25);
      }
    }
    .sidebar-header {
      font-size: 1.1rem;
      font-weight: 600;
      letter-spacing: 1px;
    }
    .dashboard-card {
      border-radius: 18px !important;
      box-shadow: 0 4px 24px rgba(37, 99, 235, 0.07);
      transition: transform 0.15s, box-shadow 0.15s;
      background: #fff;
      margin-bottom: 24px;
    }
    .dashboard-card:hover {
      transform: translateY(-4px) scale(1.03);
      box-shadow: 0 8px 32px rgba(37, 99, 235, 0.12);
    }
    .dashboard-card .icon {
      display: flex;
      align-items: center;
      justify-content: center;
      width: 48px;
      height: 48px;
      border-radius: 50%;
      background: #e0e7ff;
      margin: 0 auto 12px auto;
      font-size: 2rem;
      color: #2563eb;
    }
    .dashboard-card .fw-bold {
      font-size: 1.2rem;
    }
    .dashboard-card .fs-4 {
      font-size: 2rem;
      font-weight: 600;
      color: #2563eb;
    }
    .btn-primary {
      background: linear-gradient(90deg, #2563eb 60%, #1e40af 100%);
      border: none;
      border-radius: 12px;
      font-weight: 600;
      transition: background 0.2s, transform 0.1s;
    }
    .btn-primary:hover {
      background: #1e40af;
      transform: scale(1.04);
    }
    .table {
      border-radius: 16px;
      overflow: hidden;
    }
    .bg-primary {
      background: #2563eb !important;
    }
    .text-success {
      color: #22c55e !important;
    }
    .text-danger {
      color: #ef4444 !important;
    }
    #sidebar {
      position: fixed;
      top: 60px;
      left: 0;
      height: calc(100vh - 60px);
      z-index: 1102;
      box-shadow: 2px 0 8px rgba(0,0,0,0.04);
      transition: width 0.5s cubic-bezier(0.4, 0.2, 0.2, 1), background 0.4s, padding 0.4s;
    }
    #sidebar.collapsed {
      width: 70px !important;
      min-width: 70px !important;
    }
    #sidebar.collapsed ul.nav {
      align-items: center;
      flex-direction: column;
      width: 100%;
      justify-content: center;
      height: 100%;
      display: flex;
    }
    #sidebar.collapsed .nav-link {
      justify-content: center !important;
      padding-left: 0 !important;
      padding-right: 0 !important;
    }
    #sidebar.collapsed .nav-item {
      width: 100%;
      display: flex;
      justify-content: center;
    }
    #sidebar.collapsed .sidebar-text {
      display: none;
    }
    #sidebar.collapsed .nav-link i {
      margin: 0 !important;
      display: flex;
      justify-content: center;
      width: 100%;
    }
    #sidebarToggleIcon {
      transition: transform 0.3s cubic-bezier(0.4, 0.2, 0.2, 1);
    }
    #sidebar.collapsed #sidebarToggleIcon {
      transform: rotate(180deg);
    }
    #sidebar .sidebar-text {
      margin-left: 12px;
      transition: margin 0.3s;
    }
    #sidebar.collapsed .sidebar-text {
      margin-left: 0;
    }
    #sidebar .sidebar-header-container .sidebar-header {
      margin-right: 16px;
    }
    #sidebar .sidebar-header-container #sidebarToggle {
      margin-left: 16px;
      transition: margin 0.3s;
    }
    #sidebar.collapsed .sidebar-header-container #sidebarToggle {
      margin-left: 0;
    }
    .calendar-card {
      width: 100%;
      max-width: 340px;
      background: #fff;
      border-radius: 24px;
      box-shadow: 0 4px 24px rgba(37, 99, 235, 0.07);
      padding: 1.5rem;
      margin-top: 2rem;
    }
    .calendar-table th, .calendar-table td {
      min-width: 32px;
      height: 32px;
      text-align: center;
      vertical-align: middle;
      font-size: 1rem;
      padding: 0.25rem;
    }


    @media (max-width: 991.98px) {
  .mobile-menu-btn {
    display: block;
    margin-left: 130px;
  }

  .mobile-menu-btn {
    display: block;
    margin-left: 90px;
  }
}


/* Base: hidden by default */
.mobile-menu-btn {
  display: none;
}

/* Tablet and smaller */
@media (max-width: 991.98px) {
  .mobile-menu-btn {
    display: block;
    margin-left: 130px;
  }
}

/* Phones like itel A50 (<= 500px) */
@media (max-width: 500px) {
  .mobile-menu-btn {
    margin-left: 90px;
  }
}






 @media (max-width: 991.98px) {
      .mobile-menu-btn {
        display: block;
        margin-left: 130px;
      }
      

      .mobile-menu-btn {
        display: block;
        margin-left: 90px; /* Adjust as needed for other devices */
      }


      .elecom-logo {
        margin-left: 0;
      }
      
      .electoral-commission-title {
        font-size: 1.1rem !important;
      }

      .navbar .container-fluid {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.5rem;
        padding-right: 56px !important;
        position: relative;
      }

      .navbar .d-flex.align-items-center {
        flex-direction: row;
        align-items: center;
        gap: 0.5rem;
      }

      .navbar-brand {
        font-size: 1.1rem !important;
      }

      #sidebar {
        position: fixed;
        right: -240px;
        left: auto;
        top: 60px;
        height: calc(100vh - 60px);
        z-index: 1102;
        transform: translateX(0);
        transition: right 0.3s cubic-bezier(0.4,0.2,0.2,1);
        box-shadow: -2px 0 8px rgba(0,0,0,0.10);
        width: 240px !important;
        min-width: 60px !important;
        background: linear-gradient(135deg, #232526 0%, #2563eb 100%) !important;
        padding-top: 1.5rem !important;
      }

      #sidebar.active {
        right: 0;
        left: auto;
      }

      .main-content {
        margin-left: 0;
        margin-top: 1.5rem;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: flex-start;
        width: 100%;
        padding-left: 0 !important;
        padding-right: 0 !important;
      }

      .main-content .row.g-4 {
        flex-direction: column;
        align-items: center;
        width: 100%;
        margin: 0;
      }

      .dashboard-card, .calendar-card {
        width: 95vw;
        max-width: 400px;
        margin-left: auto;
        margin-right: auto;
      }

      .col-12, .col-lg-8, .col-lg-4 {
        width: 100% !important;
        max-width: 100% !important;
        flex: 0 0 100% !important;
        padding-left: 0 !important;
        padding-right: 0 !important;
      }

      .calendar-card {
        margin-top: 1.5rem;
      }

      #sidebar .nav-link {
        font-size: 1.1rem;
        padding: 16px 18px;
      }

      #sidebar .sidebar-header {
        font-size: 1.1rem;
      }

      .sidebar-overlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100vw;
        height: 100vh;
        background: rgba(0,0,0,0.25);
        z-index: 1100;
      }

      .sidebar-overlay.active {
        display: block;
      }
    }





    @media (max-width: 991.98px) {
      .mobile-menu-btn {
        display: block;
        margin-left: 90px; 
      }

      .elecom-logo {
        margin-left: 0;
      }
      
      .electoral-commission-title {
        font-size: 1.1rem !important;
      }

      .navbar .container-fluid {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.5rem;
        padding-right: 56px !important;
        position: relative;
      }

      .navbar .d-flex.align-items-center {
        flex-direction: row;
        align-items: center;
        gap: 0.5rem;
      }

      .navbar-brand {
        font-size: 1.1rem !important;
      }

      #sidebar {
        position: fixed;
        right: -240px;
        left: auto;
        top: 60px;
        height: calc(100vh - 60px);
        z-index: 1102;
        transform: translateX(0);
        transition: right 0.3s cubic-bezier(0.4,0.2,0.2,1);
        box-shadow: -2px 0 8px rgba(0,0,0,0.10);
        width: 240px !important;
        min-width: 60px !important;
        background: linear-gradient(135deg, #232526 0%, #2563eb 100%) !important;
        padding-top: 1.5rem !important;
      }

      #sidebar.active {
        right: 0;
        left: auto;
      }

      .main-content {
        margin-left: 0;
        margin-top: 1.5rem;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: flex-start;
        width: 100%;
        padding-left: 0 !important;
        padding-right: 0 !important;
      }

      .main-content .row.g-4 {
        flex-direction: column;
        align-items: center;
        width: 100%;
        margin: 0;
      }

      .dashboard-card, .calendar-card {
        width: 95vw;
        max-width: 400px;
        margin-left: auto;
        margin-right: auto;
      }

      .col-12, .col-lg-8, .col-lg-4 {
        width: 100% !important;
        max-width: 100% !important;
        flex: 0 0 100% !important;
        padding-left: 0 !important;
        padding-right: 0 !important;
      }

      .calendar-card {
        margin-top: 1.5rem;
      }

      #sidebar .nav-link {
        font-size: 1.1rem;
        padding: 16px 18px;
      }

      #sidebar .sidebar-header {
        font-size: 1.1rem;
      }

      .sidebar-overlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100vw;
        height: 100vh;
        background: rgba(0,0,0,0.25);
        z-index: 1100;
      }

      .sidebar-overlay.active {
        display: block;
      }
    }
    @media (min-width: 992px) {
      body {
        padding-top: 0;
      }
      .calendar-card {
        position: sticky;
        top: 80px;
        z-index: 100;
      }
    }
    .main-content {
      margin-left: 240px;
      transition: margin-left 0.5s cubic-bezier(0.4, 0.2, 0.2, 1);
      margin-top: 3rem;
    }
    .body-sidebar-collapsed .main-content {
      margin-left: 70px;
    }
    @media (min-width: 576px) {
      .modal-dialog {
        margin-top: 90px;
      }
    }
    @media (max-width: 575.98px) {
      .modal-dialog {
        margin-top: 60px;
      }
    }
    .electoral-commission-title {
      font-family: 'Libre Baskerville', serif;
      font-weight: 700;
      letter-spacing: 0.5px;
      text-transform: uppercase;
      background: linear-gradient(45deg, #ffffff, #e0e7ff);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      text-shadow: 2px 2px 4px rgba(0,0,0,0.1);
    }
    @media (min-width: 576px) and (max-width: 991.98px) {
      .main-content {
        margin: 60px 0 0 0 !important;
        padding: 1rem !important;
        width: 100% !important;
      }
      .calendar-card {
        display: none !important;
      }
      .col-12.col-lg-8 {
        width: 100% !important;
        max-width: 100% !important;
        padding: 0.5rem !important;
      }
      .dashboard-card {
        margin: 0.5rem 0 !important;
        width: 100% !important;
      }
      .container-fluid {
        max-width: 100% !important;
        overflow-x: hidden !important;
      }
    }
    .mobile-menu-btn {
      display: none;
      background: transparent;
      border: none;
      padding: 8px;
      cursor: pointer;
      transition: transform 0.3s ease;
    }
    
    .mobile-menu-btn:hover {
      transform: scale(1.1);
    }
    
    .mobile-menu-btn:active {
      transform: scale(0.95);
    }

    /* Add Candidate Modal Mobile Styles */
    #addCandidateModal .modal-dialog {
      margin-top: 80px;
    }

    #addCandidateModal .modal-content {
      border-radius: 15px;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }

    #addCandidateModal .modal-header {
      padding: 1rem;
      border-bottom: 1px solid #e5e7eb;
    }

    #addCandidateModal .modal-body {
      padding: 1.5rem;
    }

    .profile-avatar-container {
      width: 160px;
      margin: 0 auto;
      position: relative;
    }
    .profile-avatar {
      width: 150px;
      height: 150px;
      border-radius: 50%;
      border: 4px solid #2563eb;
      background: #f8f9fa;
      overflow: hidden;
      box-shadow: 0 4px 24px rgba(37,99,235,0.10);
      transition: box-shadow 0.2s, transform 0.2s;
    }
    .profile-avatar:hover {
      box-shadow: 0 8px 32px rgba(37,99,235,0.18);
      transform: scale(1.03);
    }
    .btn-edit-avatar {
      position: absolute;
      bottom: 10px;
      right: 10px;
      border-radius: 50%;
      padding: 0.5rem 0.6rem;
      font-size: 1.1rem;
      z-index: 2;
    }
    .profile-card {
      background: #fff;
      border-radius: 18px;
      box-shadow: 0 4px 24px rgba(37,99,235,0.07);
      padding: 2.5rem 2rem;
      margin-top: 2rem;
      max-width: 500px;
      margin-left: auto;
      margin-right: auto;
    }
    .profile-card-back-btn-wrapper {
      width: 100%;
      display: flex;
      justify-content: flex-start;
      margin-bottom: 1.2rem;
      position: relative;
      z-index: 2;
    }
    .profile-back-btn {
      border-radius: 50px;
      background: #fff;
      color: #2563eb;
      border: 1.5px solid #e5e7eb;
      font-weight: 500;
      box-shadow: 0 2px 8px rgba(37,99,235,0.06);
      transition: background 0.18s, color 0.18s, box-shadow 0.18s;
    }
    .profile-back-btn:hover, .profile-back-btn:focus {
      background: #2563eb;
      color: #fff;
      border-color: #2563eb;
      box-shadow: 0 4px 16px rgba(37,99,235,0.13);
    }
    @media (min-width: 992px) {
      .profile-card-back-btn-wrapper {
        position: absolute;
        top: 32px;
        left: 32px;
        width: auto;
        margin-bottom: 0;
      }
      .profile-back-btn {
        min-width: 90px;
        padding-left: 1.2rem;
        padding-right: 1.2rem;
      }
    }
    @media (max-width: 575.98px) {
      .profile-card-back-btn-wrapper {
        margin-bottom: 0.7rem;
      }
      .profile-back-btn {
        width: 100%;
        justify-content: center;
        font-size: 1rem;
        padding-left: 0.8rem;
        padding-right: 0.8rem;
      }
    }
    .dropdown-menu {
      border: none;
      padding: 0.5rem;
    }
    
    .dropdown-item {
      padding: 0.75rem 1rem;
      border-radius: 8px;
      transition: all 0.2s ease;
    }
    
    .dropdown-item:hover {
      background-color: #f8fafc;
    }
    
    .dropdown-item i {
      font-size: 1.1rem;
    }
    
    .dropdown-divider {
      margin: 0.5rem 0;
      opacity: 0.1;
    }
    
    @media (max-width: 575.98px) {
      .dropdown-menu {
        width: 200px;
        margin-top: 0.5rem !important;
        margin-left: -1rem !important;
      }
      
      .dropdown-item {
        padding: 0.75rem 1rem;
      }
    }

    #viewCandidateTable tbody tr:hover {
    background-color:rgb(193, 196, 197);
    
    }

    @media (max-width: 575.98px) {
      .dropdown-menu[aria-labelledby="mobileProfileDropdown"] {
        left: 10px !important;
        right: auto !important;
        min-width: 180px;
        margin-top: 8px !important;
        transform: none !important;
        border-radius: 12px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.12);
      }
    }

    .department-section {
      background: #fff;
      border-radius: 16px;
      padding: 2rem;
      margin-bottom: 2rem;
      box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    }

    .department-header {
      margin-bottom: 2rem;
      padding-bottom: 1rem;
      border-bottom: 2px solid #e5e7eb;
    }

    .department-title {
      color: #1f2937;
      font-size: 1.5rem;
      font-weight: 700;
      margin-bottom: 0.5rem;
    }

    .department-subtitle {
      color: #6b7280;
      font-size: 0.9rem;
    }

    .position-section {
      background: #f8fafc;
      padding: 1.5rem;
      border-radius: 12px;
      margin-bottom: 1.5rem;
    }

    .position-header {
      margin-bottom: 1.5rem;
    }

    .position-title {
      font-size: 1.2rem;
      font-weight: 600;
      color: #1f2937;
      margin-bottom: 0.5rem;
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }

    .position-subtitle {
      color: #6b7280;
      font-size: 0.85rem;
    }

    .candidates-list {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
      gap: 1rem;
    }

    .candidate-card {
      background: #fff;
      border: 1px solid #e5e7eb;
      border-radius: 6px;
      padding: 0.5rem;
      cursor: pointer;
      transition: all 0.2s ease;
      display: flex;
      gap: 0.5rem;
      align-items: center;
      position: relative;
      overflow: hidden;
    }

    .candidate-card::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: repeating-linear-gradient(
        45deg,
        rgba(37, 99, 235, 0.05),
        rgba(37, 99, 235, 0.05) 10px,
        rgba(37, 99, 235, 0.1) 10px,
        rgba(37, 99, 235, 0.1) 20px
      );
      opacity: 0;
      transition: opacity 0.3s ease;
    }

    .candidate-card:hover::before {
      opacity: 1;
    }

    .candidate-card.selected {
      border-color: #2563eb;
      background: #f8fafc;
    }

    .candidate-card.selected::before {
      opacity: 1;
      background: repeating-linear-gradient(
        45deg,
        rgba(37, 99, 235, 0.1),
        rgba(37, 99, 235, 0.1) 10px,
        rgba(37, 99, 235, 0.15) 10px,
        rgba(37, 99, 235, 0.15) 20px
      );
    }

    .candidate-card.selected::after {
      content: '✓';
      position: absolute;
      top: 50%;
      right: 10px;
      transform: translateY(-50%);
      color: #2563eb;
      font-weight: bold;
      font-size: 1.2rem;
      background: #fff;
      width: 24px;
      height: 24px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      box-shadow: 0 2px 4px rgba(37, 99, 235, 0.2);
    }

    .candidate-photo {
      width: 40px;
      height: 40px;
      border-radius: 50%;
      object-fit: cover;
      border: 1px solid #e5e7eb;
      transition: all 0.2s ease;
      position: relative;
      z-index: 1;
    }

    .candidate-card.selected .candidate-photo {
      border-color: #2563eb;
      box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.2);
    }

    .candidate-info {
      flex: 1;
      position: relative;
      z-index: 1;
    }

    .candidate-name {
      font-weight: 600;
      color: #1f2937;
      font-size: 0.85rem;
      margin-bottom: 0.1rem;
    }

    .candidate-department {
      color: #6b7280;
      font-size: 0.75rem;
      margin-bottom: 0.15rem;
    }

    .candidate-platform {
      font-size: 0.7rem;
      color: #4b5563;
      padding-top: 0.15rem;
      border-top: 1px solid #e5e7eb;
    }

    /* Add styles for the position section to enhance ballot feel */
    .position-section {
      background: #f8fafc;
      padding: 0.75rem;
      border-radius: 6px;
      margin-bottom: 0.75rem;
      border: 1px solid #e5e7eb;
      box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
    }

    .position-header {
      margin-bottom: 0.75rem;
      padding-bottom: 0.5rem;
      border-bottom: 1px dashed #e5e7eb;
    }

    .position-title {
      font-size: 0.9rem;
      margin-bottom: 0.15rem;
      color: #1f2937;
      font-weight: 600;
    }

    .position-subtitle {
      font-size: 0.7rem;
      color: #6b7280;
    }

    /* Update the badge style */
    .badge {
      font-size: 0.65rem;
      padding: 0.15rem 0.35rem;
      border-radius: 4px;
      background: rgba(37, 99, 235, 0.1);
      color: #2563eb;
      font-weight: 500;
    }

    #voteForm button[type="submit"] {
      width: 100%;
      padding: 1rem;
      font-size: 1.1rem;
      font-weight: 600;
      margin-top: 2rem;
      background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
      border: none;
      border-radius: 12px;
      transition: all 0.2s ease;
    }

    #voteForm button[type="submit"]:not(:disabled):hover {
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(37, 99, 235, 0.2);
    }

    #voteForm button[type="submit"]:disabled {
      background: #e5e7eb;
      cursor: not-allowed;
    }

    .candidate-card {
      background: #fff;
      border: 1px solid #e5e7eb;
      border-radius: 6px;
      padding: 0.5rem;
      cursor: pointer;
      transition: all 0.2s ease;
      display: flex;
      gap: 0.5rem;
      align-items: center;
    }

    .candidate-photo {
      width: 40px;
      height: 40px;
      border-radius: 50%;
      object-fit: cover;
      border: 1px solid #e5e7eb;
    }

    .candidate-name {
      font-weight: 600;
      color: #1f2937;
      font-size: 0.85rem;
      margin-bottom: 0.1rem;
    }

    .candidate-department {
      color: #6b7280;
      font-size: 0.75rem;
      margin-bottom: 0.15rem;
    }

    .candidate-platform {
      font-size: 0.7rem;
      color: #4b5563;
      padding-top: 0.15rem;
      border-top: 1px solid #e5e7eb;
    }

    .department-section {
      background: #fff;
      border-radius: 8px;
      padding: 1rem;
      margin-bottom: 1rem;
      box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
    }

    .department-header {
      margin-bottom: 1rem;
      padding-bottom: 0.5rem;
      border-bottom: 1px solid #e5e7eb;
    }

    .department-title {
      font-size: 1.1rem;
      margin-bottom: 0.15rem;
    }

    .department-subtitle {
      font-size: 0.75rem;
    }

    .position-section {
      background: #f8fafc;
      padding: 0.75rem;
      border-radius: 6px;
      margin-bottom: 0.75rem;
    }

    .position-header {
      margin-bottom: 0.75rem;
    }

    .position-title {
      font-size: 0.9rem;
      margin-bottom: 0.15rem;
    }

    .position-subtitle {
      font-size: 0.7rem;
    }

    .candidates-list {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 0.5rem;
    }

    .badge {
      font-size: 0.65rem;
      padding: 0.15rem 0.35rem;
      border-radius: 4px;
    }

    @keyframes slideIn {
      from {
        transform: translateY(-10px);
        opacity: 0;
      }
      to {
        transform: translateY(0);
        opacity: 1;
      }
    }

    @keyframes shake {
      0%, 100% { transform: translateX(0); }
      25% { transform: translateX(-5px); }
      75% { transform: translateX(5px); }
    }

    @keyframes shake {
  0% { transform: translateX(0); }
  25% { transform: translateX(-5px); }
  50% { transform: translateX(5px); }
  75% { transform: translateX(-5px); }
  100% { transform: translateX(0); }
}

.shake {
  animation: shake 0.5s ease;
}

.notification-icon{
  margin-right: 30px;
}

  </style>
</head>
<body>
  <!-- Smoke Effect Canvas -->
    <canvas id="smoke-canvas" style="position:fixed;top:0;left:0;width:100vw;height:100vh;z-index:0;pointer-events:none;"></canvas>
    
    <audio src="assets/welcomeBG.mp3" autoplay hidden></audio>
  <!--<audio src="assets/studentBG.mp3" autoplay loop hidden></audio>-->
  <!-- Top Bar -->
 <nav class="navbar navbar-expand-lg position-sticky" style="background: linear-gradient(100deg,rgb(8, 53, 145),rgb(126, 132, 146)); height: 60px;">


    <div class="container-fluid px-2">
      <div class="d-flex align-items-center justify-content-between w-100">
        <div class="d-flex align-items-center">

        
        
          <div class="dropdown">
            <a href="#" class="btn btn-link text-light d-lg-none p-0 d-flex align-items-center gap-2" style="font-size: 1.5rem; text-decoration: none;" role="button" id="mobileProfileDropdown" data-bs-toggle="dropdown" aria-expanded="false">
              <?php if ($profile_picture): ?>
                <img src="<?php echo $profile_picture; ?>" alt="Profile Picture" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover; border: 2px solid #fff;">
              <?php else: ?>
                <i class="bi bi-person-circle"></i>
              <?php endif; ?>
              <span class="d-lg-none" style="font-size: 1rem; font-weight: 500; color: white; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 120px;">
                <?php echo htmlspecialchars($user_profile['full_name'] ?? ''); ?>
              </span>
            </a>
            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="mobileProfileDropdown" style="margin-top: 0.5rem; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.1);">
              <li><a class="dropdown-item d-flex align-items-center gap-2" href="profile.php"><i class="bi bi-person"></i> Profile</a></li>
              <li><hr class="dropdown-divider"></li>
              <li><a class="dropdown-item d-flex align-items-center gap-2 text-danger" href="logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
            </ul>
          </div>
          
          <img src="../img/icon.png" alt="Electoral Commission Logo" class="elecom-logo d-none d-lg-block" style="width:44px; height:44px; background:#fff; border-radius:50%; margin-right:14px; box-shadow:0 2px 8px rgba(37,99,235,0.10);">
          <span class="navbar-brand mb-0 h1 electoral-commission-title d-none d-lg-block" style="font-size:1.5rem;">Electoral Commission</span>
        </div>
        <!-- Add this inside your navbar or header -->
        <!-- Shows only on small screens and below -->
        <button class="btn d-lg-none mobile-menu-btn" id="mobileMenuBtn">
          <i class="bi bi-list text-white" style="font-size: 2rem;"></i>
        </button>


      </div>


        <!-- Notification Icon -->
        <a href="#" class="notification-icon position-relative me-3 d-none d-lg-inline-flex" title="Notifications">
          <i class="bi bi-bell" style="font-size: 1.2rem; color: #fff;"></i>
          <span class="notification-badge position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size: 0.7rem;">3</span>
        </a>

      <div class="profile-button d-none d-lg-block">
      
        <div class="dropdown">
          <a href="#" class="btn btn-outline-light rounded-pill d-flex align-items-center" style="font-weight:500; min-width:120px;" role="button" id="desktopProfileDropdown" data-bs-toggle="dropdown" aria-expanded="false">
            <?php if ($profile_picture): ?>
              <img src="<?php echo $profile_picture; ?>" alt="use data to see photos" style="width: 32px; height: 32px; border-radius: 50%; object-fit: cover; margin-right: 8px;">
            <?php else: ?>
              <span class="d-flex align-items-center justify-content-center" style="width:32px; height:32px; background:#e0e7ef; border-radius:50%; margin-right:8px;">
                <i class="bi bi-person-circle" style="font-size:1.5rem; color:#2563eb;"></i>
              </span>
            <?php endif; ?>
            <span style="white-space:nowrap; overflow:hidden; text-overflow:ellipsis; max-width:70px; display:inline-block; vertical-align:middle;">
              <?php echo htmlspecialchars($user_profile['full_name'] ?? ''); ?>
            </span>
          </a>
          <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="desktopProfileDropdown" style="margin-top: 0.5rem; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.1);">
            <li><a class="dropdown-item d-flex align-items-center gap-2" href="profile.php"><i class="bi bi-person"></i> Profile</a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item d-flex align-items-center gap-2 text-danger" href="logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
          </ul>
        </div>
      </div>
    </div>
  </nav>

  <div class="container-fluid p-0">
    <div class="row g-0 flex-nowrap">
      <!-- Sidebar -->
      <div id="sidebar" class="col-auto col-md-3 col-xl-2 bg-dark text-white p-4 min-vh-100 d-flex flex-column" style="box-shadow:2px 0 8px rgba(0,0,0,0.04);">
        <div class="mb-4 d-flex align-items-center justify-content-between position-relative">
          <span class="fw-bold sidebar-header sidebar-text">Dashboard</span>
          <button id="sidebarToggle" class="btn btn-secondary btn-sm rounded-circle d-none d-md-inline ms-auto"><i id="sidebarToggleIcon" class="bi bi-chevron-left"></i></button>
        </div>
        <ul class="nav flex-column gap-2">
          <li class="nav-item">
            <a class="nav-link text-white d-flex align-items-center active" href="dashboard_student.php">
              <i class="bi bi-house-door"></i>
              <span class="sidebar-text">Home</span>
            </a>
          </li>
          <!-- Sidebar Nav Item -->
          <li class="nav-item">
            <a class="nav-link text-white d-flex align-items-center" href="#" data-bs-toggle="modal" data-bs-target="#viewCandidatesModal"
              onclick="if(window.innerWidth <= 991.98){
                document.getElementById('sidebar').classList.remove('active');
                document.getElementById('sidebarOverlay').classList.remove('active');
                document.getElementById('mobileMenuBtn').classList.remove('active');
              }">
              <i class="bi bi-people"></i>
              <span class="sidebar-text">View Candidates</span>
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link text-white d-flex align-items-center" href="#" onclick="handleCastVoteClick(event)">
              <i class="bi bi-check-circle"></i>
              <span class="sidebar-text">Cast Vote</span>
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link text-white d-flex align-items-center" href="#" onclick="handleViewResultsClick(event)">
              <i class="bi bi-list-check"></i>
              <span class="sidebar-text">View Results</span>
            </a>
          </li>
        </ul>
      </div>
      <!-- Main Content -->
      <div class="col px-0 px-md-4 py-4 flex-grow-1 main-content">
        <div class="row g-4">
          <!-- Function Cards -->
          <div class="col-12 col-lg-8">
            <div class="row g-4 justify-content-center">
              <!-- Dashboard Cards -->
              <div class="col-12 col-md-6">
                <div class="dashboard-card p-4">
                  <div class="icon">
                    <i class="bi bi-check-circle"></i>
                  </div>
                  <h3 class="fw-bold text-center">Voting Status</h3>
                  <p class="fs-4 text-center mb-0" id="votingStatus">Not Voted</p>
                </div>
              </div>

              <div class="col-12 col-md-6">
                <div class="dashboard-card p-4">
                  <div class="icon">
                    <i class="bi bi-clock"></i>
                  </div>
                  <h3 class="fw-bold text-center">Time Remaining</h3>
                  <p class="fs-4 text-center mb-0" id="timeRemaining">Loading...</p>
                </div>
              </div>

              <div class="col-12 col-md-6">
                <div class="dashboard-card p-4" id="totalCandidatesCard" style="cursor: pointer;">
                  <div class="icon">
                    <i class="bi bi-people"></i>
                  </div>
                  <h3 class="fw-bold text-center">Total Candidates</h3>
                  <p class="fs-4 text-center mb-0" id="totalCandidates">0</p>
                </div>
              </div>
              <div class="col-12 col-md-6">
                <div class="dashboard-card p-4">
                  <div class="icon">
                    <i class="bi bi-person-check"></i>
                  </div>
                  <h3 class="fw-bold text-center">Total Votes Cast</h3>
                  <p class="fs-4 text-center mb-0" id="totalVotesCast">0</p>
                </div>
              </div>
            </div>
          </div>
    
          <!-- Election Timeline -->
          <div class="col-12 col-lg-4 d-none d-lg-block">
            <div class="calendar-card">
              <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0">Election Timeline</h5>
              </div>
              <div class="timeline-events">
                <div class="event-item mb-3">
                  <div class="event-date text-primary fw-bold">Start Date</div>
                  <div class="event-title" id="electionStartDate">--/--/----</div>
                </div>
                <div class="event-item mb-3">
                  <div class="event-date text-primary fw-bold">End Date</div>
                  <div class="event-title" id="electionEndDate">--/--/----</div>
                </div>
                <div class="event-item">
                  <div class="event-date text-primary fw-bold">Results Date</div>
                  <div class="event-title" id="resultsDate">--/--/----</div>
                </div>
              </div>
            </div>
          </div>


          
        </div>
      </div>
    </div>
  </div>



  
<!-- View Candidates Modal -->
<div class="modal fade" id="viewCandidatesModal" tabindex="-1" aria-labelledby="viewCandidatesModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="viewCandidatesModalLabel">All Candidates</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <!-- Search Input -->
        <div class="mb-3">
          <label for="searchViewCandidate" class="form-label">Search by Name</label>
          <input type="text" class="form-control" id="searchViewCandidate" placeholder="Search Candidate">
        </div>

        <!-- Scrollable Table -->
        <div style="max-height: 300px; overflow-y: auto;">
          <table class="table table-bordered" id="viewCandidateTable">
            <thead class="table-light">
              <tr>
                <th>Name</th>
                <th>Department</th>
                <th>Position</th>
              </tr>
            </thead>
            <tbody>
              <!-- Candidates will be inserted here -->
            </tbody>
          </table>
        </div>

        <div id="viewCandidateMsg" class="mt-2"></div>
      </div>
    </div>
  </div>
</div>




<!-- Candidate Profile Modal -->
<div class="modal fade" id="candidateProfileModal" tabindex="-1" aria-labelledby="candidateProfileModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content p-3">
      <div class="modal-header d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center gap-2">
          <img id="candidatePhoto" class="rounded-circle" alt="Candidate Photo"
               style="width: 40px; height: 40px; object-fit: cover; border: 2px solid #fff;">
          <h5 class="modal-title mb-0" id="candidateProfileModalLabel">Candidate Name</h5>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div id="candidateProfileCard" class="card shadow rounded-4 border-0">
          <div class="card-body">
            <p class="mb-2"><strong>Age:</strong> <span id="profileAge"></span></p>
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



  <div class="sidebar-overlay" id="sidebarOverlay"></div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script src="../function/dashboard.js"></script>
  <script>





// View Candidate Modal Setup
const viewCandidateModal = document.getElementById('viewCandidatesModal');
const viewTableBody = document.querySelector('#viewCandidateTable tbody');
const viewMsg = document.getElementById('viewCandidateMsg');
const viewSearchInput = document.getElementById('searchViewCandidate');

// Fetch and render candidates
function loadViewCandidateTable(searchQuery = '') {
  // Show loading message
  viewTableBody.innerHTML = '<tr><td colspan="3" class="text-center">Loading candidates...</td></tr>';
  
  fetch('../src/fetch_candidates.php')
    .then(response => {
      if (!response.ok) {
        throw new Error('Network response was not ok');
      }
      return response.json();
    })
    .then(data => {
      // Check if data has the expected structure
      if (!data || !data.candidates || !Array.isArray(data.candidates)) {
        throw new Error('Invalid data format received');
      }
      
      viewTableBody.innerHTML = '';
      
      if (data.candidates.length === 0) {
        viewTableBody.innerHTML = '<tr><td colspan="3" class="text-center">No candidates found</td></tr>';
        return;
      }

      data.candidates.forEach(candidate => {
        if (candidate.name.toLowerCase().includes(searchQuery.toLowerCase())) {
          const row = document.createElement('tr');
          row.style.cursor = 'pointer';
          row.onclick = () => showCandidateProfile(candidate);
          row.innerHTML = `
            <td>
              <div class="d-flex align-items-center gap-2">
                <img src="${candidate.photo || '../img/icon.png'}" 
                     class="rounded-circle" 
                     alt="${candidate.name}"
                     style="width: 32px; height: 32px; object-fit: cover;">
                ${candidate.name}
              </div>
            </td>
            <td>${candidate.department || 'N/A'}</td>
            <td>${candidate.position || 'N/A'}</td>
          `;
          viewTableBody.appendChild(row);
        }
      });

      // If no candidates match the search
      if (viewTableBody.children.length === 0) {
        viewTableBody.innerHTML = '<tr><td colspan="3" class="text-center">No matching candidates found</td></tr>';
      }
    })
    .catch(error => {
      console.error('Error fetching candidates:', error);
      viewTableBody.innerHTML = '<tr><td colspan="3" class="text-center text-danger">Error loading candidates. Please try again.</td></tr>';
    });
}

// Reload candidates when modal is shown
viewCandidateModal.addEventListener('shown.bs.modal', () => {
  loadViewCandidateTable();
});

// Handle search input with debounce
let searchTimeout;
viewSearchInput.addEventListener('input', (e) => {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    loadViewCandidateTable(e.target.value);
  }, 300);
});

// Show Candidate Profile Function
function showCandidateProfile(candidate) {
  const profileModalTitle = document.getElementById('candidateProfileModalLabel');
  const profileAge = document.getElementById('profileAge');
  const profileDept = document.getElementById('profileDept');
  const profilePosition = document.getElementById('profilePosition');
  const profilePlatform = document.getElementById('profilePlatform');
  const candidatePhoto = document.getElementById('candidatePhoto');

  profileModalTitle.textContent = candidate.name || 'Candidate Profile';
  profileAge.textContent = candidate.age ? `${candidate.age} years old` : 'N/A';
  profileDept.textContent = candidate.department || 'N/A';
  profilePosition.textContent = candidate.position || 'N/A';
  profilePlatform.textContent = candidate.platform || 'N/A';

  // Set candidate photo
  if (candidate.photo) {
    candidatePhoto.src = candidate.photo;
  } else {
    candidatePhoto.src = '../img/icon.png';
  }

  // Hide the candidate list modal (if open)
  const viewModal = bootstrap.Modal.getInstance(document.getElementById('viewCandidatesModal'));
  if (viewModal) {
    viewModal.hide();
  }

  // Show the candidate profile modal
  const profileModal = new bootstrap.Modal(document.getElementById('candidateProfileModal'));
  profileModal.show();
}






    // Sidebar toggle functionality
    document.getElementById('sidebarToggle').onclick = function() {
      var sidebar = document.getElementById('sidebar');
      var icon = document.getElementById('sidebarToggleIcon');
      sidebar.classList.toggle('collapsed');
      if (sidebar.classList.contains('collapsed')) {
        icon.classList.remove('bi-chevron-left');
        icon.classList.add('bi-chevron-right');
        document.body.classList.add('body-sidebar-collapsed');
      } else {
        icon.classList.remove('bi-chevron-right');
        icon.classList.add('bi-chevron-left');
        document.body.classList.remove('body-sidebar-collapsed');
      }
    };

    // Mobile menu functionality
    const mobileMenuBtn = document.getElementById('mobileMenuBtn');
    const sidebar = document.getElementById('sidebar');
    const sidebarOverlay = document.getElementById('sidebarOverlay');

    mobileMenuBtn.addEventListener('click', function() {
      this.classList.toggle('active');
      sidebar.classList.toggle('active');
      sidebarOverlay.classList.toggle('active');
    });

    sidebarOverlay.addEventListener('click', function() {
      sidebar.classList.remove('active');
      sidebarOverlay.classList.remove('active');
      mobileMenuBtn.classList.remove('active');
    });

    // Close sidebar when clicking outside on mobile
    document.addEventListener('click', function(event) {
      if (window.innerWidth <= 991.98) {
        if (!sidebar.contains(event.target) && !mobileMenuBtn.contains(event.target)) {
          sidebar.classList.remove('active');
          sidebarOverlay.classList.remove('active');
          mobileMenuBtn.classList.remove('active');
        }
      }
    });

    // Header fade effect on scroll
    window.addEventListener('scroll', function() {
      const navbar = document.querySelector('.navbar');
      if (window.scrollY > 10) {
        navbar.classList.add('faded');
      } else {
        navbar.classList.remove('faded');
      }
    });

    // Update voting status
    function updateVotingStatus() {
      fetch('../src/get_voting_status.php')
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            document.getElementById('votingStatus').textContent = data.hasVoted ? 'Voted' : 'Not Voted';
            document.getElementById('votingStatus').className = data.hasVoted ? 'fs-4 text-center mb-0 text-success' : 'fs-4 text-center mb-0 text-warning';
          }
        })
        .catch(error => {
          console.error('Error fetching voting status:', error);
        });
    }

    // Update total candidates
    function updateTotalCandidates() {
      fetch('../src/get_total_candidates.php')
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            document.getElementById('totalCandidates').textContent = data.total_candidates;
          }
        })
        .catch(error => {
          console.error('Error fetching total candidates:', error);
        });
    }

    // Update total votes cast
    function updateTotalVotesCast() {
      fetch('../src/get_total_votes_cast.php')
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            document.getElementById('totalVotesCast').textContent = data.total_votes;
            
            // Create or update the voted users list
            const votedUsersList = document.getElementById('votedUsersList');
            if (votedUsersList) {
              votedUsersList.innerHTML = data.voted_users.map(user => `
                <div class="voted-user-item">
                  <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-person-check-fill text-success"></i>
                    <span>${user.full_name}</span>
                  </div>
                  <small class="text-muted">${user.program_name}</small>
                </div>
              `).join('');
            }
          }
        })
        .catch(error => {
          console.error('Error fetching total votes cast:', error);
        });
    }



    //Election date

document.addEventListener('DOMContentLoaded', function () {
  updateElectionDates(); // Initial load
  setInterval(updateElectionDates, 60000); // Refresh every 60 seconds
});

function updateElectionDates() {
  fetch('../src/get_election_dates.php')
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        document.getElementById('electionStartDate').textContent = formatDateTime(data.start_date);
        document.getElementById('electionEndDate').textContent = formatDateTime(data.end_date);
        document.getElementById('resultsDate').textContent = formatDateTime(data.results_date);
      } else {
        console.error('Failed to load dates:', data.message);
      }
    })
    .catch(error => {
      console.error('Error fetching election dates:', error);
    });
}

function formatDateTime(dateString) {
  const date = new Date(dateString);
  if (isNaN(date)) return 'Invalid date';

  return date.toLocaleString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: 'numeric',
    minute: '2-digit',
    hour12: true
  });
}



//RealTime Countdown


let countdownInterval;

function fetchElectionDatesAndStartCountdown() {
  fetch('../src/get_election_dates.php')
    .then(response => response.json())
    .then(data => {
      const timeEl = document.getElementById('timeRemaining');

      if (data.success && data.start_date && data.end_date) {
        const startDate = new Date(data.start_date);
        const endDate = new Date(data.end_date);

        if (isNaN(startDate.getTime()) || isNaN(endDate.getTime())) {
          timeEl.textContent = 'Invalid date format';
          timeEl.style.color = 'orange';
          return;
        }

        startDynamicCountdown(startDate, endDate);
      } else {
        timeEl.textContent = 'Dates not set';
        timeEl.style.color = 'orange';
      }
    })
    .catch(error => {
      console.error('Error fetching election dates:', error);
      const timeEl = document.getElementById('timeRemaining');
      timeEl.textContent = 'Error loading timer';
      timeEl.style.color = 'orange';
    });
}

function startDynamicCountdown(startTime, endTime) {
  clearInterval(countdownInterval);
  const timeEl = document.getElementById('timeRemaining');

  function updateCountdown() {
    const now = new Date();
    let diff, label;

    if (now < startTime) {
      diff = startTime - now;
      label = 'Voting starts in ';
      timeEl.style.color = ''; // default
    } else if (now >= startTime && now < endTime) {
      diff = endTime - now;
      label = 'Voting ends in ';
      timeEl.style.color = ''; // default
    } else {
      timeEl.textContent = 'Voting has ended';
      timeEl.style.color = 'red';
      clearInterval(countdownInterval);
      return;
    }

    const hours = String(Math.floor(diff / (1000 * 60 * 60))).padStart(2, '0');
    const minutes = String(Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60))).padStart(2, '0');
    const seconds = String(Math.floor((diff % (1000 * 60)) / 1000)).padStart(2, '0');

    timeEl.textContent = label + `${hours}:${minutes}:${seconds}`;
  }

  updateCountdown();
  countdownInterval = setInterval(updateCountdown, 1000);
}

document.addEventListener('DOMContentLoaded', fetchElectionDatesAndStartCountdown);







    // Initialize all updates
    document.addEventListener('DOMContentLoaded', function() {
      updateVotingStatus();
      updateTotalCandidates();
      updateTotalVotesCast();
      updateElectionDates();
      
      // Update every 30 seconds
      setInterval(function() {
        updateVotingStatus();
        updateTotalCandidates();
        updateTotalVotesCast();
      }, 30000);
    });



    function updateTotalCandidates() {
    fetch('../src/get_total_candidates.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('totalCandidates').textContent = data.total;
            } else {
                document.getElementById('totalCandidates').textContent = 'Error';
            }
        })
        .catch(error => {
            document.getElementById('totalCandidates').textContent = 'Error';
            console.error('Error fetching total candidates:', error);
        });
}

updateTotalCandidates();

// Add click handler for total candidates card
document.getElementById('totalCandidatesCard').addEventListener('click', () => {
  const viewCandidatesModal = new bootstrap.Modal(document.getElementById('viewCandidatesModal'));
  viewCandidatesModal.show();
  loadViewCandidateTable();
});

// Function to handle casting votes
function castVote(votes) {
  // Show loading state
  const submitBtn = document.getElementById('submitVoteBtn');
  const originalBtnText = submitBtn.innerHTML;
  submitBtn.disabled = true;
  submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Submitting...';

  // Get the selected department
  const department = document.getElementById('departmentSelect').value;

  // Prepare the vote data
  const voteData = {
    department: department,
    votes: votes
  };

  // Make the AJAX request
  fetch('../src/submit_vote.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-Requested-With': 'XMLHttpRequest'
    },
    body: JSON.stringify(voteData)
  })
  .then(response => response.json())
  .then(data => {
    if (data.success) {
      // Show success message
      const alertDiv = document.createElement('div');
      alertDiv.className = 'alert alert-success alert-dismissible fade show';
      alertDiv.innerHTML = `
        <div class="d-flex align-items-center">
          <i class="bi bi-check-circle-fill me-2"></i>
          <div>
            <strong>Success!</strong> ${data.message}
          </div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      `;
      document.querySelector('.modal-body').insertBefore(alertDiv, document.querySelector('#candidatesContainer'));
      
      // Update UI
      updateVotingStatus();
      updateTotalVotesCast();

      // Close modal after success
      const castVoteModal = bootstrap.Modal.getInstance(document.getElementById('castVoteModal'));
      if (castVoteModal) {
        castVoteModal.hide();
      }
      
      // Remove alert after 5 seconds
      setTimeout(() => {
        alertDiv.remove();
      }, 5000);
    } else {
      throw new Error(data.message || 'Error recording vote');
    }
  })
  .catch(error => {
      // Show error message
      const alertDiv = document.createElement('div');
      alertDiv.className = 'alert alert-danger alert-dismissible fade show';
      alertDiv.innerHTML = `
      <div class="d-flex align-items-center">
        <i class="bi bi-exclamation-circle-fill me-2"></i>
        <div>
          <strong>Error!</strong> ${error.message}
        </div>
      </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      `;
    document.querySelector('.modal-body').insertBefore(alertDiv, document.querySelector('#candidatesContainer'));
      
      // Remove alert after 5 seconds
      setTimeout(() => {
        alertDiv.remove();
      }, 5000);
  })
  .finally(() => {
    // Reset button state
    submitBtn.disabled = false;
    submitBtn.innerHTML = originalBtnText;
  });
}

// Function to validate votes before submission
function validateVotes() {
  const selectedCandidates = document.querySelectorAll('.candidate-card.selected');
  const department = document.getElementById('departmentSelect').value;
  
  if (!department) {
    throw new Error('Please select a department');
  }

  if (selectedCandidates.length === 0) {
    throw new Error('Please select at least one candidate');
  }

  // Get all selected candidate IDs
  const votes = Array.from(selectedCandidates).map(card => {
    return card.getAttribute('data-candidate-id');
  });

  return votes;
}

// Handle vote submission with validation
document.getElementById('submitVoteBtn').addEventListener('click', function() {
  try {
    const votes = validateVotes();
    castVote(votes);
  } catch (error) {
    // Show validation error
    const alertDiv = document.createElement('div');
    alertDiv.className = 'alert alert-warning alert-dismissible fade show';
    alertDiv.innerHTML = `
      <div class="d-flex align-items-center">
        <i class="bi bi-exclamation-triangle-fill me-2"></i>
        <div>
          <strong>Validation Error!</strong> ${error.message}
        </div>
      </div>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    `;
    document.querySelector('.modal-body').insertBefore(alertDiv, document.querySelector('#candidatesContainer'));
    
    // Remove alert after 3 seconds
    setTimeout(() => {
      alertDiv.remove();
    }, 3000);
  }
});

// Add animation classes
document.head.insertAdjacentHTML('beforeend', `
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
`);

// Function to check election status
function checkElectionStatus() {
  fetch('../src/get_election_dates.php')
    .then(response => response.json())
    .then(data => {
      const statusEl = document.getElementById('electionStatus');
      const departmentSelection = document.getElementById('departmentSelection');
      const candidatesContainer = document.getElementById('candidatesContainer');
      const submitVoteBtn = document.getElementById('submitVoteBtn');
      
      if (!data.success) {
        statusEl.className = 'alert alert-danger';
        statusEl.innerHTML = '<i class="bi bi-exclamation-circle me-2"></i>Error checking election status.';
        submitVoteBtn.disabled = true;
        return;
      }

      const now = new Date();
      const startDate = new Date(data.start_date);
      const endDate = new Date(data.end_date);

      if (isNaN(startDate.getTime()) || isNaN(endDate.getTime())) {
        statusEl.className = 'alert alert-warning';
        statusEl.innerHTML = '<i class="bi bi-exclamation-circle me-2"></i>Election dates not properly set.';
        submitVoteBtn.disabled = true;
        return;
      }

      if (now < startDate) {
        statusEl.className = 'alert alert-warning';
        statusEl.innerHTML = `<i class="bi bi-clock me-2"></i>Voting has not started yet. Starts on ${formatDateTime(data.start_date)}`;
        departmentSelection.style.display = 'none';
        candidatesContainer.innerHTML = '';
        submitVoteBtn.disabled = true;
      } else if (now > endDate) {
        statusEl.className = 'alert alert-danger';
        statusEl.innerHTML = `<i class="bi bi-x-circle me-2"></i>Voting period has ended on ${formatDateTime(data.end_date)}`;
        departmentSelection.style.display = 'none';
        candidatesContainer.innerHTML = '';
        submitVoteBtn.disabled = true;
      } else {
        statusEl.className = 'alert alert-success';
        statusEl.innerHTML = '<i class="bi bi-check-circle me-2"></i>Voting is currently active.';
        departmentSelection.style.display = 'block';
        updateSubmitButtonState(); // Update button state based on selections
        // Load candidates if voting is active and department is selected
        const department = document.getElementById('departmentSelect').value;
        if (department) {
          loadCandidatesByDepartment(department);
        }
      }
    })
    .catch(error => {
      console.error('Error checking election status:', error);
      const statusEl = document.getElementById('electionStatus');
      statusEl.className = 'alert alert-danger';
      statusEl.innerHTML = '<i class="bi bi-exclamation-circle me-2"></i>Error checking election status. Please try again.';
      document.getElementById('submitVoteBtn').disabled = true;
    });
}

// Function to format date and time
function formatDateTime(dateString) {
  const date = new Date(dateString);
  if (isNaN(date)) return 'Invalid date';

  return date.toLocaleString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: 'numeric',
    minute: '2-digit',
    hour12: true
  });
}

// Handle Cast Vote click
function handleCastVoteClick(event) {
  event.preventDefault();
  
  // Mobile sidebar handling
  if (window.innerWidth <= 991.98) {
    document.getElementById('sidebar').classList.remove('active');
    document.getElementById('sidebarOverlay').classList.remove('active');
    document.getElementById('mobileMenuBtn').classList.remove('active');
  }
  
  // Show the voting modal
  const castVoteModal = new bootstrap.Modal(document.getElementById('castVoteModal'));
  castVoteModal.show();
  
  // Check election status when modal is shown
  checkElectionStatus();
}

// Add event listener for modal shown
document.getElementById('castVoteModal').addEventListener('shown.bs.modal', function () {
  checkElectionStatus();
});

  </script>

<!-- Cast Vote Modal -->
<div class="modal fade" id="castVoteModal">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header py-3 border-bottom">
        <h5 class="modal-title d-flex align-items-center gap-2">
          <i class="bi bi-check-circle-fill text-primary"></i>
          Cast Your Vote
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <!-- Election Status -->
        <div id="electionStatus" class="alert mb-4"></div>
        
        <!-- Already Voted Message -->
        <div id="alreadyVotedMessage" class="text-center py-5 d-none">
          <div class="mb-4">
            <i class="bi bi-check-circle-fill text-success" style="font-size: 4rem;"></i>
          </div>
          <h4 class="mb-3">Thank You for Voting!</h4>
          <p class="text-muted mb-4">You have already cast your vote. Your participation is greatly appreciated.</p>
          <button type="button" class="btn btn-outline-primary" data-bs-dismiss="modal">
            <i class="bi bi-x-circle me-2"></i>Close
          </button>
        </div>

        <!-- Voting Interface -->
        <div id="votingInterface">
        <!-- Department Selection -->
          <div id="departmentSelection" class="mb-4">
            <label class="form-label fw-bold">Select Department</label>
            <select class="form-select form-select-lg" id="departmentSelect">
              <option value="">Choose a department...</option>
              <option value="PAFE">PAFE (PRIME Association of Future Educators)</option>
              <option value="SITE">SITE (Society of Information Technology Enthusiasts)</option>
              <option value="AFPROTECHS">AFPROTECHS (Association of Food Processing Technology Students)</option>
            </select>
          </div>

          <!-- Candidates Container -->
          <div id="candidatesContainer" class="candidates-wrapper">
            <!-- Candidates will be loaded here -->
          </div>

          <!-- Action Buttons -->
          <div class="d-flex justify-content-between align-items-center mt-4 pt-3 border-top">
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
              <i class="bi bi-x-circle me-2"></i>Cancel
            </button>
            <button type="button" class="btn btn-success" id="submitVoteBtn" onclick="submitVote()">
              <i class="bi bi-check-circle me-2"></i>Submit Vote
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<style>
/* Already Voted Message Styles */
#alreadyVotedMessage {
  background: #f8fafc;
  border-radius: 16px;
  padding: 3rem 2rem;
  margin: 1rem 0;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
}

#alreadyVotedMessage .bi-check-circle-fill {
  color: #059669;
  animation: scaleIn 0.5s ease-out;
}

@keyframes scaleIn {
  0% {
    transform: scale(0);
    opacity: 0;
  }
  50% {
    transform: scale(1.2);
  }
  100% {
    transform: scale(1);
    opacity: 1;
  }
}

/* Enhanced Form Select Styles */
.form-select-lg {
  padding: 1rem 1.5rem;
  font-size: 1.1rem;
  border-radius: 12px;
  border: 2px solid #e5e7eb;
  transition: all 0.2s ease;
  background-color: #fff;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
}

.form-select-lg:focus {
  border-color: #2563eb;
  box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}

.form-select-lg:hover {
  border-color: #2563eb;
}

/* Progress Indicator Styles */
.progress-indicator {
  padding: 1rem;
  background: #fff;
  border-radius: 12px;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
}

.step {
  display: flex;
  flex-direction: column;
  align-items: center;
  position: relative;
  z-index: 1;
}

.step-number {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  background: #e5e7eb;
  color: #6b7280;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 600;
  margin-bottom: 0.5rem;
  transition: all 0.3s ease;
}

.step.active .step-number {
  background: #2563eb;
  color: #fff;
}

.step-text {
  font-size: 0.875rem;
  color: #6b7280;
  font-weight: 500;
}

.step.active .step-text {
  color: #2563eb;
}

.step-line {
  flex: 1;
  height: 2px;
  background: #e5e7eb;
  margin: 0 1rem;
  margin-top: 16px;
}

/* Candidate Card Styles */
.candidate-card {
  background: #fff;
  border: 1px solid #e5e7eb;
  border-radius: 12px;
  padding: 1rem;
  margin-bottom: 1rem;
  cursor: pointer;
  transition: all 0.2s ease;
  display: flex;
  align-items: center;
  gap: 1rem;
}

.candidate-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
  border-color: #2563eb;
}

.candidate-card.selected {
  border-color: #2563eb;
  background: #f0f7ff;
}

.candidate-photo {
  width: 64px;
  height: 64px;
  border-radius: 50%;
  object-fit: cover;
  border: 2px solid #e5e7eb;
  transition: all 0.2s ease;
}

.candidate-card.selected .candidate-photo {
  border-color: #2563eb;
}

.candidate-info {
  flex: 1;
}

.candidate-name {
  font-weight: 600;
  color: #1f2937;
  margin-bottom: 0.25rem;
}

.candidate-platform {
  font-size: 0.875rem;
  color: #6b7280;
  margin-bottom: 0;
}

/* Vote Summary Styles */
.vote-summary {
  background: #f8fafc;
  border-radius: 12px;
  padding: 1.5rem;
  margin-top: 2rem;
}

.selected-candidates-list {
  display: grid;
  gap: 1rem;
}

.selected-candidate-item {
  background: #fff;
  border-radius: 8px;
  padding: 1rem;
  display: flex;
  align-items: center;
  gap: 1rem;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
}
</style>

<script>
// Function to check voting status
function checkVotingStatus() {
  fetch('../src/get_voting_status.php')
    .then(response => response.json())
    .then(data => {
      const votingInterface = document.getElementById('votingInterface');
      const alreadyVotedMessage = document.getElementById('alreadyVotedMessage');
      
      if (data.success && data.hasVoted) {
        votingInterface.classList.add('d-none');
        alreadyVotedMessage.classList.remove('d-none');
        } else {
        votingInterface.classList.remove('d-none');
        alreadyVotedMessage.classList.add('d-none');
      }
    })
    .catch(error => {
      console.error('Error checking voting status:', error);
    });
}

// Function to load candidates by department
function loadCandidatesByDepartment(department) {
  const container = document.getElementById('candidatesContainer');
  container.innerHTML = '<div class="text-center py-4"><div class="spinner-border text-primary" role="status"></div></div>';

  fetch('../src/fetch_candidates.php')
    .then(response => response.json())
    .then(data => {
      if (data.success && Array.isArray(data.candidates)) {
        // Get both USG candidates and department-specific candidates
        const usgCandidates = data.candidates.filter(c => c.department === 'USG');
        const departmentCandidates = data.candidates.filter(c => c.department === department);
        
        // Combine USG and department candidates
        const allCandidates = [...usgCandidates, ...departmentCandidates];
        
        if (allCandidates.length === 0) {
          container.innerHTML = '<div class="alert alert-info">No candidates found for this department.</div>';
          return;
        }

        // Group candidates by position and department
        const positions = {};
        allCandidates.forEach(candidate => {
          const key = `${candidate.position}_${candidate.department}`;
          if (!positions[key]) {
            positions[key] = {
              position: candidate.position,
              department: candidate.department,
              candidates: []
            };
          }
          positions[key].candidates.push(candidate);
        });

        let html = '';
        // First show USG candidates
        Object.values(positions)
          .filter(group => group.department === 'USG')
          .forEach(group => {
            const voteLimit = group.position === 'Representative' ? 2 : 1;
            html += `
              <div class="position-section mb-4">
                <div class="position-header">
                  <h6 class="position-title mb-2">
                    ${group.position} (USG)
                    <span class="badge bg-primary ms-2">${voteLimit} vote${voteLimit > 1 ? 's' : ''} allowed</span>
                  </h6>
                </div>
                <div class="candidates-list">
                  ${group.candidates.map(candidate => `
                    <div class="candidate-card" 
                         onclick="selectCandidate(this, '${group.position}', ${candidate.candidate_id})"
                         data-position="${group.position}"
                         data-candidate-id="${candidate.candidate_id}">
                      <img src="${candidate.photo || '../img/icon.png'}" 
                           alt="${candidate.name}" 
                           class="candidate-photo">
                      <div class="candidate-info">
                        <div class="candidate-name">${candidate.name}</div>
                        <div class="candidate-platform">${candidate.platform || 'No platform available'}</div>
                      </div>
                    </div>
                  `).join('')}
                </div>
              </div>
            `;
          });

        // Then show department-specific candidates
        Object.values(positions)
          .filter(group => group.department === department)
          .forEach(group => {
            const voteLimit = group.position === 'Representative' ? 2 : 1;
            html += `
              <div class="position-section mb-4">
                <div class="position-header">
                  <h6 class="position-title mb-2">
                    ${group.position} (${department})
                    <span class="badge bg-primary ms-2">${voteLimit} vote${voteLimit > 1 ? 's' : ''} allowed</span>
                  </h6>
                </div>
                <div class="candidates-list">
                  ${group.candidates.map(candidate => `
                    <div class="candidate-card" 
                         onclick="selectCandidate(this, '${group.position}', ${candidate.candidate_id})"
                         data-position="${group.position}"
                         data-candidate-id="${candidate.candidate_id}">
                      <img src="${candidate.photo || '../img/icon.png'}" 
                           alt="${candidate.name}" 
                           class="candidate-photo">
                      <div class="candidate-info">
                        <div class="candidate-name">${candidate.name}</div>
                        <div class="candidate-platform">${candidate.platform || 'No platform available'}</div>
                      </div>
                    </div>
                  `).join('')}
                </div>
              </div>
            `;
          });

        container.innerHTML = html;
        updateSubmitButtonState();
      } else {
        container.innerHTML = '<div class="alert alert-danger">Error loading candidates. Please try again.</div>';
      }
    })
    .catch(error => {
      console.error('Error:', error);
      container.innerHTML = '<div class="alert alert-danger">Error loading candidates. Please try again.</div>';
    });
}

// Function to select a candidate
function selectCandidate(element, position, candidateId) {
  const positionSection = element.closest('.position-section');
  const voteLimits = {
    'Representative': 2,
    'default': 1
  };
  const voteLimit = voteLimits[position] || voteLimits.default;

  // Count selected candidates in this section
  let selectedCandidates = positionSection.querySelectorAll('.candidate-card.selected');

  // If already selected, deselect
  if (element.classList.contains('selected')) {
    element.classList.remove('selected');
  } else {
    if (selectedCandidates.length >= voteLimit) {
      showAlert('warning', `You can only select ${voteLimit} candidate${voteLimit > 1 ? 's' : ''} for ${position}.`);
      return;
    }
    element.classList.add('selected');
  }

  // Update the badge to show remaining votes
  selectedCandidates = positionSection.querySelectorAll('.candidate-card.selected'); // update after change
  const badge = positionSection.querySelector('.badge');
  const remainingVotes = voteLimit - selectedCandidates.length;
  if (selectedCandidates.length === 0) {
    badge.textContent = `${voteLimit} vote${voteLimit > 1 ? 's' : ''} allowed`;
  } else {
    badge.textContent = `${remainingVotes} vote${remainingVotes !== 1 ? 's' : ''} remaining`;
  }

  updateSubmitButtonState();
}

// Function to update submit button state
function updateSubmitButtonState() {
  const submitBtn = document.getElementById('submitVoteBtn');
  const selectedCandidates = document.querySelectorAll('.candidate-card.selected');
  const department = document.getElementById('departmentSelect').value;
  const electionStatus = document.getElementById('electionStatus');
  
  // Check if voting is active
  const isVotingActive = electionStatus.classList.contains('alert-success');
  
  // Enable submit button only if:
  // 1. Voting is active
  // 2. Department is selected
  // 3. At least one candidate is selected
  submitBtn.disabled = !isVotingActive || !department || selectedCandidates.length === 0;
}

// Function to handle vote submission
function handleVoteSubmission() {
  const submitBtn = document.getElementById('submitVoteBtn');
  const selectedCandidates = document.querySelectorAll('.candidate-card.selected');
  const department = document.getElementById('departmentSelect').value;

  // Validate department selection
  if (!department) {
    showAlert('warning', 'Please select a department first.');
    return;
  }

  // Validate candidate selection
  if (selectedCandidates.length === 0) {
    showAlert('warning', 'Please select at least one candidate.');
    return;
  }

  // Show confirmation dialog
  const confirmDiv = document.createElement('div');
  confirmDiv.className = 'alert alert-info alert-dismissible fade show';
  confirmDiv.innerHTML = `
    <div class="d-flex align-items-center">
      <i class="bi bi-info-circle-fill me-2"></i>
      <div>
        <strong>Confirm Your Vote</strong>
        <p class="mb-0 mt-1">Are you sure you want to submit your vote? This action cannot be undone.</p>
        <div class="mt-2">
          <button type="button" class="btn btn-primary btn-sm me-2" onclick="submitVote()">
            <i class="bi bi-check-circle me-1"></i>Yes, Submit Vote
          </button>
          <button type="button" class="btn btn-outline-secondary btn-sm" onclick="this.closest('.alert').remove()">
            <i class="bi bi-x-circle me-1"></i>Cancel
          </button>
        </div>
      </div>
    </div>
  `;
  document.querySelector('.modal-body').insertBefore(confirmDiv, document.querySelector('#candidatesContainer'));
}

// Function to submit vote
function submitVote() {
  const submitBtn = document.getElementById('submitVoteBtn');
  const selectedCandidates = document.querySelectorAll('.candidate-card.selected');
  const department = document.getElementById('departmentSelect').value;

  // Validate department selection
  if (!department) {
    alert('Please select a department first.');
    return;
  }

  // Validate candidate selection
  if (selectedCandidates.length === 0) {
    alert('Please select at least one candidate.');
    return;
  }

  // Show loading state
  submitBtn.disabled = true;
  submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Submitting...';

  // Get selected candidates
  const votes = Array.from(selectedCandidates).map(card => card.getAttribute('data-candidate-id'));
  const voteData = {
    department: department,
    votes: votes
  };

  // Submit vote
  fetch('../src/submit_vote.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-Requested-With': 'XMLHttpRequest'
    },
    body: JSON.stringify(voteData)
  })
    .then(response => response.json())
    .then(data => {
      if (data.success) {
      alert('Vote submitted successfully!');
      // Update UI
      updateVotingStatus();
      updateTotalVotesCast();
      // Close modal
      const castVoteModal = bootstrap.Modal.getInstance(document.getElementById('castVoteModal'));
      if (castVoteModal) {
        castVoteModal.hide();
      }
      } else {
      alert('Error: ' + (data.message || 'Failed to submit vote'));
      }
    })
    .catch(error => {
    alert('Error: ' + error.message);
  })
  .finally(() => {
    // Reset button state
    submitBtn.disabled = false;
    submitBtn.innerHTML = '<i class="bi bi-check-circle me-2"></i>Submit Vote';
  });
}

// Function to show alerts
function showAlert(type, message) {
  const alertDiv = document.createElement('div');
  alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
  alertDiv.innerHTML = `
    <div class="d-flex align-items-center">
      <i class="bi bi-${type === 'success' ? 'check-circle' : type === 'warning' ? 'exclamation-triangle' : 'exclamation-circle'}-fill me-2"></i>
      <div>
        <strong>${type === 'success' ? 'Success!' : type === 'warning' ? 'Warning!' : 'Error!'}</strong> ${message}
      </div>
    </div>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
  `;
  document.querySelector('.modal-body').insertBefore(alertDiv, document.querySelector('#candidatesContainer'));
  
  // Remove alert after 5 seconds
  setTimeout(() => {
    alertDiv.remove();
  }, 5000);
}

// Function to update submit button state
function updateSubmitButtonState() {
  const submitBtn = document.getElementById('submitVoteBtn');
  const selectedCandidates = document.querySelectorAll('.candidate-card.selected');
  const department = document.getElementById('departmentSelect').value;
  const electionStatus = document.getElementById('electionStatus');
  
  // Check if voting is active
  const isVotingActive = electionStatus.classList.contains('alert-success');
  
  // Enable submit button only if:
  // 1. Voting is active
  // 2. Department is selected
  // 3. At least one candidate is selected
  submitBtn.disabled = !isVotingActive || !department || selectedCandidates.length === 0;
}

// Add event listener for department selection
document.getElementById('departmentSelect').addEventListener('change', function(e) {
  const department = e.target.value;
  if (department) {
    loadCandidatesByDepartment(department);
  } else {
    document.getElementById('candidatesContainer').innerHTML = '';
    updateSubmitButtonState();
  }
});

// Function to select a candidate
function selectCandidate(element, position, candidateId) {
  const positionSection = element.closest('.position-section');
  const voteLimits = {
    'Representative': 2,
    'default': 1
  };
  const voteLimit = voteLimits[position] || voteLimits.default;

  // Count selected candidates in this section
  let selectedCandidates = positionSection.querySelectorAll('.candidate-card.selected');

  // If already selected, deselect
  if (element.classList.contains('selected')) {
    element.classList.remove('selected');
  } else {
    if (selectedCandidates.length >= voteLimit) {
      showAlert('warning', `You can only select ${voteLimit} candidate${voteLimit > 1 ? 's' : ''} for ${position}.`);
      return;
    }
    element.classList.add('selected');
  }

  // Update the badge to show remaining votes
  selectedCandidates = positionSection.querySelectorAll('.candidate-card.selected'); // update after change
  const badge = positionSection.querySelector('.badge');
  const remainingVotes = voteLimit - selectedCandidates.length;
  if (selectedCandidates.length === 0) {
    badge.textContent = `${voteLimit} vote${voteLimit > 1 ? 's' : ''} allowed`;
  } else {
    badge.textContent = `${remainingVotes} vote${remainingVotes !== 1 ? 's' : ''} remaining`;
  }

  updateSubmitButtonState();
}

// Check voting status when modal is shown
document.getElementById('castVoteModal').addEventListener('shown.bs.modal', function () {
  checkVotingStatus();
  checkElectionStatus();
});

function confirmVote() {
  const submitBtn = document.getElementById('submitVoteBtn');
  const originalBtnText = submitBtn.innerHTML;
  
  // Show loading state
  submitBtn.disabled = true;
  submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Submitting...';

  // Get selected candidates and department
  const selectedCandidates = document.querySelectorAll('.candidate-card.selected');
  const department = document.getElementById('departmentSelect').value;
  
  // Prepare vote data
  const votes = Array.from(selectedCandidates).map(card => card.getAttribute('data-candidate-id'));
  const voteData = {
    department: department,
    votes: votes
  };

  // Submit vote
  fetch('../src/submit_vote.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-Requested-With': 'XMLHttpRequest'
    },
    body: JSON.stringify(voteData)
  })
  .then(response => response.json())
  .then(data => {
    if (data.success) {
      // Show success message
      const successDiv = document.createElement('div');
      successDiv.className = 'alert alert-success alert-dismissible fade show';
      successDiv.innerHTML = `
        <div class="d-flex align-items-center">
          <i class="bi bi-check-circle-fill me-2"></i>
          <div>
            <strong>Success!</strong> Your vote has been successfully recorded.
          </div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      `;
      document.querySelector('.modal-body').insertBefore(successDiv, document.querySelector('#candidatesContainer'));
      
      // Update UI
      updateVotingStatus();
      updateTotalVotesCast();

      // Close modal after 2 seconds
      setTimeout(() => {
        const castVoteModal = bootstrap.Modal.getInstance(document.getElementById('castVoteModal'));
        if (castVoteModal) {
          castVoteModal.hide();
        }
      }, 2000);
    } else {
      throw new Error(data.message || 'Error recording vote');
    }
  })
  .catch(error => {
    // Show error message
    const errorDiv = document.createElement('div');
    errorDiv.className = 'alert alert-danger alert-dismissible fade show';
    errorDiv.innerHTML = `
      <div class="d-flex align-items-center">
        <i class="bi bi-exclamation-circle-fill me-2"></i>
        <div>
          <strong>Error!</strong> ${error.message}
        </div>
      </div>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    `;
    document.querySelector('.modal-body').insertBefore(errorDiv, document.querySelector('#candidatesContainer'));
  })
  .finally(() => {
    // Reset button state
    submitBtn.disabled = false;
    submitBtn.innerHTML = originalBtnText;
  });
}

// Function to show alerts
function showAlert(type, message) {
  const alertDiv = document.createElement('div');
  alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
  alertDiv.innerHTML = `
    <div class="d-flex align-items-center">
      <i class="bi bi-${type === 'success' ? 'check-circle' : type === 'warning' ? 'exclamation-triangle' : 'exclamation-circle'}-fill me-2"></i>
      <div>
        <strong>${type === 'success' ? 'Success!' : type === 'warning' ? 'Warning!' : 'Error!'}</strong> ${message}
      </div>
    </div>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
  `;
  document.querySelector('.modal-body').insertBefore(alertDiv, document.querySelector('#candidatesContainer'));
  
  // Remove alert after 5 seconds
  setTimeout(() => {
    alertDiv.remove();
  }, 5000);
}

// Function to handle View Results click
function handleViewResultsClick(event) {
    event.preventDefault();
    
    // Mobile sidebar handling
    if (window.innerWidth <= 991.98) {
        document.getElementById('sidebar').classList.remove('active');
        document.getElementById('sidebarOverlay').classList.remove('active');
        document.getElementById('mobileMenuBtn').classList.remove('active');
    }

    // Check election timeline
    fetch('../src/get_election_dates.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const current_time = new Date();
                const end_date = new Date(data.end_date);
                const results_date = new Date(data.results_date);

                // Create and show modal
                const modalHtml = `
                    <div class="modal fade" id="resultsTimelineModal" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Election Results</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <div id="electionStatus" class="alert mb-4"></div>
                                    <div class="text-center py-4">
                                        <i class="bi bi-clock-history fs-1 text-muted mb-3"></i>
                                        <p class="mb-0" id="resultsMessage"></p>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                </div>
                            </div>
                        </div>
                    </div>
                `;

                // Remove existing modal if any
                const existingModal = document.getElementById('resultsTimelineModal');
                if (existingModal) {
                    existingModal.remove();
                }

                // Add new modal to body
                document.body.insertAdjacentHTML('beforeend', modalHtml);

                // Show modal
                const modal = new bootstrap.Modal(document.getElementById('resultsTimelineModal'));
                modal.show();

                // Update status and message
                const statusEl = document.getElementById('electionStatus');
                const messageEl = document.getElementById('resultsMessage');

                if (current_time < end_date) {
                    // Voting hasn't ended yet
                    statusEl.className = 'alert alert-warning';
                    statusEl.innerHTML = `<i class="bi bi-clock me-2"></i>Voting is still in progress`;
                    messageEl.textContent = 'Results will be available after the election ends.';
                } else if (current_time >= end_date && current_time < results_date) {
                    // Voting has ended but results aren't released yet
                    statusEl.className = 'alert alert-info';
                    statusEl.innerHTML = `<i class="bi bi-info-circle me-2"></i>Voting period has ended`;
                    messageEl.textContent = `Results will be available after ${formatDateTime(data.results_date)}`;
                } else if (current_time >= results_date) {
                    // Both voting has ended and results are released
                    window.location.href = 'results.php';
                }
            } else {
                alert('Error checking election timeline. Please try again later.');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error checking election timeline. Please try again later.');
        });
}

// Function to format date and time
function formatDateTime(dateString) {
    const date = new Date(dateString);
    if (isNaN(date)) return 'Invalid date';

    return date.toLocaleString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: 'numeric',
        minute: '2-digit',
        hour12: true
    });
}




 // Enhanced Mobile Legends-style smoke effect
    (function() {
      const canvas = document.getElementById('smoke-canvas');
      if (!canvas) return;
      const ctx = canvas.getContext('2d');
      let width = window.innerWidth;
      let height = window.innerHeight;
      canvas.width = width;
      canvas.height = height;
      window.addEventListener('resize', () => {
        width = window.innerWidth;
        height = window.innerHeight;
        canvas.width = width;
        canvas.height = height;
      });

      // Smoke particle system (layered, swirling)
      const layers = 3;
      const particlesPerLayer = 18;
      const allParticles = [];
      const baseColors = [
        'rgba(14, 80, 179, 0.1)', // blue
        'rgba(255,255,255,0.08)', // white
        'rgba(37,99,235,0.09)'   // deep blue
      ];
      function randomBetween(a, b) { return a + Math.random() * (b - a); }
      function createParticle(layer) {
        const baseRadius = [90, 60, 40][layer];
        return {
          x: randomBetween(0, width),
          y: randomBetween(height * 0.2, height * 0.8),
          radius: randomBetween(baseRadius, baseRadius + 40),
          color: baseColors[layer],
          alpha: randomBetween(0.13, 0.22) + layer * 0.05,
          speed: randomBetween(0.12, 0.32) + layer * 0.08,
          swirl: randomBetween(0.002, 0.008) * (Math.random() > 0.5 ? 1 : -1),
          angle: randomBetween(0, Math.PI * 2),
          layer
        };
      }
      for (let l = 0; l < layers; l++) {
        for (let i = 0; i < particlesPerLayer; i++) {
          allParticles.push(createParticle(l));
        }
      }
      function draw() {
        ctx.clearRect(0, 0, width, height);
        for (let p of allParticles) {
          ctx.save();
          ctx.globalAlpha = p.alpha;
          ctx.beginPath();
          ctx.arc(p.x, p.y, p.radius, 0, 2 * Math.PI);
          ctx.fillStyle = p.color;
          ctx.shadowColor = p.color;
          ctx.shadowBlur = 60 - p.layer * 15;
          ctx.fill();
          ctx.restore();
          // Swirl and float
          p.angle += p.swirl;
          p.x += Math.cos(p.angle) * (0.3 + p.layer * 0.2);
          p.y -= p.speed;
          // Respawn if out of view
          if (p.y + p.radius < 0 || p.x + p.radius < 0 || p.x - p.radius > width) {
            Object.assign(p, createParticle(p.layer));
            p.y = height + p.radius;
          }
        }
        requestAnimationFrame(draw);
      }
      draw();
    })();

// ... rest of the existing code ...
</script>

<style>
.candidate-card {
  background: #fff;
  border: 1px solid #e5e7eb;
  border-radius: 12px;
  padding: 1rem;
  margin-bottom: 1rem;
  cursor: pointer;
  transition: all 0.2s ease;
  display: flex;
  align-items: center;
  gap: 1rem;
}

.candidate-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
  border-color: #2563eb;
}

.candidate-card.selected {
  border-color: #2563eb;
  background: #f0f7ff;
}

.candidate-photo {
  width: 64px;
  height: 64px;
  border-radius: 50%;
  object-fit: cover;
  border: 2px solid #e5e7eb;
  transition: all 0.2s ease;
}

.candidate-card.selected .candidate-photo {
  border-color: #2563eb;
}

.candidate-info {
  flex: 1;
}

.candidate-name {
  font-weight: 600;
  color: #1f2937;
  margin-bottom: 0.25rem;
}

.candidate-platform {
  font-size: 0.875rem;
  color: #6b7280;
  margin-bottom: 0;
}

.position-section {
  background: #f8fafc;
  padding: 1.5rem;
  border-radius: 12px;
  margin-bottom: 1.5rem;
}

.position-header {
  margin-bottom: 1.5rem;
}

.position-title {
  font-size: 1.2rem;
  font-weight: 600;
  color: #1f2937;
  margin-bottom: 0.5rem;
}

.badge {
  font-size: 0.75rem;
  padding: 0.35rem 0.65rem;
  border-radius: 6px;
  background: rgba(37, 99, 235, 0.1);
  color: #2563eb;
  font-weight: 500;
}

.form-select-lg {
  padding: 1rem 1.5rem;
  font-size: 1.1rem;
  border-radius: 12px;
  border: 2px solid #e5e7eb;
  transition: all 0.2s ease;
  background-color: #fff;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
}

.form-select-lg:focus {
  border-color: #2563eb;
  box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}

.form-select-lg:hover {
  border-color: #2563eb;
}
</style>
</body>
</html>