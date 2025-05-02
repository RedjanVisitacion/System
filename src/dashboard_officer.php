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

$profile_picture = !empty($user_profile['profile_picture']) && file_exists('../uploads/profile_pictures/' . $user_profile['profile_picture'])
    ? '../uploads/profile_pictures/' . htmlspecialchars($user_profile['profile_picture'])
    : null;

// ✅ Add Candidate Submission Logic
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['full_name'], $_POST['department'], $_POST['position'], $_POST['info'])) {
    header('Content-Type: application/json');

    $name = trim($_POST['full_name']);
    $department = trim($_POST['department']);
    $position = trim($_POST['position']);
    $platform = trim($_POST['info']);

    // Check if any of the fields are empty
    if ($name === '' || $department === '' || $position === '' || $platform === '') {
        echo json_encode(['success' => false, 'message' => 'All fields are required.']);
        exit;
    }

    // Check if the candidate with the same name, department, and position already exists
    $check_stmt = $con->prepare("SELECT candidate_id FROM candidate WHERE name = ? AND department = ? AND position = ?");
    $check_stmt->bind_param("sss", $name, $department, $position);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();

    if ($check_result->num_rows > 0) {
        // Candidate with this name, department, and position already exists
        echo json_encode(['success' => false, 'message' => 'Candidate with this name, department, and position already exists.']);
        exit;
    }

    // Insert the new candidate into the database
    $stmt = $con->prepare("INSERT INTO candidate (name, department, position, platform) VALUES (?, ?, ?, ?)");
    if ($stmt) {
        $stmt->bind_param("ssss", $name, $department, $position, $platform);
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Candidate added successfully.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to insert candidate.']);
        }
        $stmt->close();
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error.']);
    }
    exit;
}

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
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <style>
    body {
      font-family: 'Poppins', 'Segoe UI', Arial, sans-serif;
      background: #f6fafd;
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
        margin-right: 10px;
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
  </style>
</head>
<body>
  <!-- Top Bar -->
  <nav class="navbar navbar-expand-lg position-relative" style="background: #2563eb; height: 60px;">
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
        <button class="mobile-menu-btn" id="mobileMenuBtn" style="padding: 0.25rem;">
          <i class="bi bi-list text-white" style="font-size: 2rem;"></i>
        </button>
      </div>
      <div class="profile-button d-none d-lg-block">
        <div class="dropdown">
          <a href="#" class="btn btn-outline-light rounded-pill d-flex align-items-center" style="font-weight:500; min-width:120px;" role="button" id="desktopProfileDropdown" data-bs-toggle="dropdown" aria-expanded="false">
            <?php if ($profile_picture): ?>
              <img src="<?php echo $profile_picture; ?>" alt="Profile Picture" style="width: 32px; height: 32px; border-radius: 50%; object-fit: cover; margin-right: 8px;">
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
            <a class="nav-link text-white d-flex align-items-center active" href="officer_dashboard.php">
              <i class="bi bi-house-door"></i>
              <span class="sidebar-text">Home</span>
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link text-white d-flex align-items-center" href="candidates.php">
              <i class="bi bi-people"></i>
              <span class="sidebar-text">Candidates</span>
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link text-white d-flex align-items-center" href="#" data-bs-toggle="modal" data-bs-target="#addCandidateModal" onclick="if(window.innerWidth <= 991.98){document.getElementById('sidebar').classList.remove('active');document.getElementById('sidebarOverlay').classList.remove('active');document.getElementById('mobileMenuBtn').classList.remove('active');}">
              <i class="bi bi-plus-circle"></i>
              <span class="sidebar-text">Add Candidate</span>
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link text-white d-flex align-items-center" href="#" data-bs-toggle="modal" data-bs-target="#removeCandidateModal" onclick="if(window.innerWidth <= 991.98){document.getElementById('sidebar').classList.remove('active');document.getElementById('sidebarOverlay').classList.remove('active');document.getElementById('mobileMenuBtn').classList.remove('active');}">
               <i class="bi bi-trash"></i>
                <span class="sidebar-text">Remove Candidate</span>
            </a>
          </li>

          <li class="nav-item">
            <a class="nav-link text-white d-flex align-items-center" href="results.php">
              <i class="bi bi-list-check"></i>
              <span class="sidebar-text">All Results</span>
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link text-white d-flex align-items-center" href="generate_report.php">
              <i class="bi bi-file-earmark-bar-graph"></i>
              <span class="sidebar-text">Generate Report</span>
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
                  <h3 class="fw-bold text-center">Total Voters</h3>
                  <p class="fs-4 text-center mb-0" id="totalVoters">0</p>
                </div>
              </div>
              <div class="col-12 col-md-6">
                <div class="dashboard-card p-4">
                  <div class="icon">
                    <i class="bi bi-check-circle"></i>
                  </div>
                  <h3 class="fw-bold text-center">Votes Cast</h3>
                  <p class="fs-4 text-center mb-0" id="votesCast">0</p>
                </div>
              </div>
              <div class="col-12 col-md-6">
                <div class="dashboard-card p-4">
                  <div class="icon">
                    <i class="bi bi-clock"></i>
                  </div>
                  <h3 class="fw-bold text-center">Time Remaining</h3>
                  <p class="fs-4 text-center mb-0" id="timeRemaining">--:--:--</p>
                </div>
              </div>
            </div>
          </div>
          <!-- Calendar Card -->
          <div class="col-12 col-lg-4 d-none d-lg-block">
            <div class="calendar-card">
              <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0">Calendar</h5>
                <div class="btn-group">
                  <button class="btn btn-sm btn-outline-primary" id="prevMonthBtn"><i class="bi bi-chevron-left"></i></button>
                  <button class="btn btn-sm btn-outline-primary" id="todayBtn">Today</button>
                  <button class="btn btn-sm btn-outline-primary" id="nextMonthBtn"><i class="bi bi-chevron-right"></i></button>
                </div>
              </div>
              <h6 class="text-center mb-3" id="calendarMonthYear"></h6>
              <table class="table table-bordered calendar-table">
                <thead>
                  <tr>
                    <th>Su</th>
                    <th>Mo</th>
                    <th>Tu</th>
                    <th>We</th>
                    <th>Th</th>
                    <th>Fr</th>
                    <th>Sa</th>
                  </tr>
                </thead>
                <tbody id="calendarBody"></tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  <div class="sidebar-overlay" id="sidebarOverlay"></div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script src="../function/dashboard.js"></script>
  <!-- Add Candidate Modal -->
  <div class="modal fade" id="addCandidateModal" tabindex="-1" aria-labelledby="addCandidateModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="addCandidateModalLabel">Add Candidate</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form id="addCandidateForm">
          <div class="row g-3">
            <div class="col-12 col-md-6">
              <label for="full_name" class="form-label">Full Name</label>
              <input type="text" class="form-control" id="full_name" name="full_name" required>
            </div>
            <div class="col-12 col-md-6">
              <label for="department" class="form-label">Department</label>
              <select class="form-select" id="department" name="department" required>
                <option value="">Select Department</option>
                <option value="USG">USG</option>
                <option value="BSIT">BSIT (SITE Officers)</option>
                <option value="BFPT">BFPT (AFPROTECHS)</option>
                <option value="BTLED">BTLED (PAFE)</option>
              </select>
            </div>
            <div class="col-12 col-md-6">
              <label for="candidatePosition" class="form-label">Position</label>
              <select class="form-select" id="candidatePosition" name="position" required>
                <option value="">Select Position</option>
              </select>
            </div>
            <div class="col-12">
              <label for="info" class="form-label">Candidate Information</label>
              <textarea class="form-control" id="info" name="info" rows="3" placeholder="Brief background or platform..." required></textarea>
            </div>
          </div>
          <button type="submit" class="btn btn-primary w-100 mt-3">Add Candidate</button>
        </form>
        <div id="addCandidateMsg" class="mt-2"></div>
      </div>
    </div>
  </div>
</div>


<!-- Remove Candidate Modal -->
<div class="modal fade" id="removeCandidateModal" tabindex="-1" aria-labelledby="removeCandidateModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="removeCandidateModalLabel">Remove Candidate</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form id="removeCandidateForm">
          <input type="hidden" name="candidate_id" id="selectedCandidateId" required>
          <div class="table-responsive">
            <table class="table table-bordered table-hover" id="candidateTable">
              <thead class="table-light">
                <tr>
                  <th>Name</th>
                  <th>Position</th>
                  <th>Department</th>
                  <th>Action</th>
                </tr>
              </thead>
              <tbody>
                <!-- Rows will be populated dynamically -->
              </tbody>
            </table>
          </div>
          <button type="submit" class="btn btn-danger w-100 mt-3" id="removeBtn" disabled>Remove Selected Candidate</button>
        </form>
        <div id="removeCandidateMsg" class="mt-2"></div>
      </div>
    </div>
  </div>
</div>



  <script>
  const departmentSelect = document.getElementById("department");
const positionSelect = document.getElementById("candidatePosition");

const positions = {
  USG: [
    "President",
    "Vice-President",
    "General Secretary",
    "Associate Secretary",
    "Treasurer",
    "Auditor",
    "PIO",
    "BTLED Representative",
    "BSIT Representative",
    "BFPT Representative"
  ],
  BSIT: [
    "President",
    "Vice-President",
    "General Secretary",
    "Associate Secretary",
    "Treasurer",
    "Auditor",
    "PIO"
  ],
  BFPT: [
    "President",
    "Vice-President",
    "General Secretary",
    "Associate Secretary",
    "Treasurer",
    "Auditor",
    "PIO"
  ],
  BTLED: [
    "President",
    "Vice-President",
    "General Secretary",
    "Associate Secretary",
    "Treasurer",
    "Auditor",
    "PIO"
  ]
};

departmentSelect.addEventListener("change", () => {
  const dept = departmentSelect.value;
  positionSelect.innerHTML = `<option value="">Select Position</option>`;
  if (positions[dept]) {
    positions[dept].forEach(pos => {
      const option = document.createElement("option");
      option.value = pos.toLowerCase().replace(/\s+/g, "_");
      option.textContent = pos;
      positionSelect.appendChild(option);
    });
  }
});

// Handle form submission
document.getElementById('addCandidateForm').onsubmit = function(e) {
  e.preventDefault();
  var form = this;
  var msg = document.getElementById('addCandidateMsg');
  msg.textContent = '';
  var formData = new FormData(form);
  fetch('dashboard_officer.php', {
    method: 'POST',
    body: formData
  })
  .then(r => r.json())
  .then(response => {
    if (response.success) {
      msg.textContent = response.message || 'Candidate added successfully!';
      msg.className = 'text-success mt-2';
      form.reset();  // Reset the form after successful submission
    } else {
      msg.textContent = response.message || 'Error adding candidate.';
      msg.className = 'text-danger mt-2';
    }
  })
  .catch(() => {
    msg.textContent = 'Error connecting to server.';
    msg.className = 'text-danger mt-2';
  });
};


//Remove Candidate
const removeCandidateModal = document.getElementById('removeCandidateModal');

removeCandidateModal.addEventListener('shown.bs.modal', function () {
  const tableBody = document.querySelector('#candidateTable tbody');
  const selectedInput = document.getElementById('selectedCandidateId');
  const removeBtn = document.getElementById('removeBtn');
  const msg = document.getElementById('removeCandidateMsg');
  
  // Reset
  tableBody.innerHTML = '<tr><td colspan="4">Loading...</td></tr>';
  selectedInput.value = '';
  removeBtn.disabled = true;
  msg.textContent = '';

  fetch('fetch_candidates.php')
    .then(response => response.json())
    .then(data => {
      if (data.success && Array.isArray(data.candidates) && data.candidates.length > 0) {
        tableBody.innerHTML = '';
        data.candidates.forEach(candidate => {
          const row = document.createElement('tr');
          row.innerHTML = `
            <td>${candidate.name}</td>
            <td>${candidate.position}</td>
            <td>${candidate.department}</td>
            <td><button type="button" class="btn btn-sm btn-outline-danger select-btn" data-id="${candidate.candidate_id}">Select</button></td>
          `;
          tableBody.appendChild(row);
        });

        // Add click listener to each "Select" button
        document.querySelectorAll('.select-btn').forEach(btn => {
          btn.addEventListener('click', () => {
            const id = btn.getAttribute('data-id');
            selectedInput.value = id;
            removeBtn.disabled = false;

            // Visually mark selected row
            document.querySelectorAll('#candidateTable tbody tr').forEach(tr => tr.classList.remove('table-active'));
            btn.closest('tr').classList.add('table-active');
          });
        });
      } else {
        tableBody.innerHTML = '<tr><td colspan="4">No candidates found</td></tr>';
      }
    })
    .catch(error => {
      console.error('Error fetching candidates:', error);
      tableBody.innerHTML = '<tr><td colspan="4">Error loading candidates</td></tr>';
    });
});

// Handle removal
document.getElementById('removeCandidateForm').onsubmit = function (e) {
  e.preventDefault();

  const form = this;
  const msg = document.getElementById('removeCandidateMsg');
  msg.textContent = '';
  const formData = new FormData(form);

  fetch('remove_candidate.php', {
    method: 'POST',
    body: formData
  })
    .then(r => r.json())
    .then(response => {
      if (response.success) {
        msg.textContent = response.message || 'Candidate removed successfully!';
        msg.className = 'text-success mt-2';
        form.reset();
        document.getElementById('removeBtn').disabled = true;

        // Refresh the candidate list
        removeCandidateModal.dispatchEvent(new CustomEvent('shown.bs.modal'));
      } else {
        msg.textContent = response.message || 'Error removing candidate.';
        msg.className = 'text-danger mt-2';
      }
    })
    .catch(() => {
      msg.textContent = 'Error connecting to server.';
      msg.className = 'text-danger mt-2';
    });
};




  //Sidebar Function
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
  // Calendar functionality
  const calendarMonthYear = document.getElementById('calendarMonthYear');
  const calendarBody = document.getElementById('calendarBody');
  const prevMonthBtn = document.getElementById('prevMonthBtn');
  const nextMonthBtn = document.getElementById('nextMonthBtn');
  const todayBtn = document.getElementById('todayBtn');

  let today = new Date();
  let currentMonth = today.getMonth();
  let currentYear = today.getFullYear();

  function renderCalendar(month, year) {
    const monthNames = [
      'January', 'February', 'March', 'April', 'May', 'June',
      'July', 'August', 'September', 'October', 'November', 'December'
    ];
    calendarMonthYear.textContent = `${monthNames[month]} ${year}`;

    // First day of the month
    const firstDay = new Date(year, month, 1).getDay();
    // Number of days in the month
    const daysInMonth = new Date(year, month + 1, 0).getDate();

    let date = 1;
    let rows = '';
    for (let i = 0; i < 6; i++) { // 6 weeks max
      let row = '<tr>';
      for (let j = 0; j < 7; j++) {
        if (i === 0 && j < firstDay) {
          row += '<td class="bg-light rounded-2"></td>';
        } else if (date > daysInMonth) {
          row += '<td class="bg-light rounded-2"></td>';
        } else {
          let isToday = (date === today.getDate() && month === today.getMonth() && year === today.getFullYear());
          row += `<td class="${isToday ? 'bg-primary text-white rounded-2' : 'bg-light rounded-2'}">${date}</td>`;
          date++;
        }
      }
      row += '</tr>';
      rows += row;
      if (date > daysInMonth) break;
    }
    calendarBody.innerHTML = rows;
  }

  renderCalendar(currentMonth, currentYear);

  prevMonthBtn.onclick = function() {
    currentMonth--;
    if (currentMonth < 0) {
      currentMonth = 11;
      currentYear--;
    }
    renderCalendar(currentMonth, currentYear);
  };
  nextMonthBtn.onclick = function() {
    currentMonth++;
    if (currentMonth > 11) {
      currentMonth = 0;
      currentYear++;
    }
    renderCalendar(currentMonth, currentYear);
  };
  todayBtn.onclick = function() {
    currentMonth = today.getMonth();
    currentYear = today.getFullYear();
    renderCalendar(currentMonth, currentYear);
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
  function updateTotalVoters() {
    fetch('../src/get_total_voters.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('totalVoters').textContent = data.total_voters;
            } else {
                document.getElementById('totalVoters').textContent = 'Error';
            }
        })
        .catch(error => {
            document.getElementById('totalVoters').textContent = 'Error';
            console.error('Error fetching total voters:', error);
        });
  }
  updateTotalVoters();
  document.addEventListener('DOMContentLoaded', function() {
    const editBtn = document.getElementById('editProfilePicBtn');
    const options = document.getElementById('editPhotoOptions');
    const changeBtn = document.getElementById('changePhotoBtn');
    const cancelBtn = document.getElementById('cancelEditPhotoBtn');
    const fileInput = document.getElementById('profile-picture-input');
    const deleteBtn = document.getElementById('deletePhotoBtn');
    const deleteModal = document.getElementById('deletePhotoModal') ? new bootstrap.Modal(document.getElementById('deletePhotoModal')) : null;

    if (editBtn) {
        if (options) {
            editBtn.addEventListener('click', function(e) {
                options.classList.toggle('d-none');
            });
        } else {
            editBtn.addEventListener('click', function(e) {
                fileInput.click();
            });
        }
    }
    if (cancelBtn) {
        cancelBtn.addEventListener('click', function(e) {
            options.classList.add('d-none');
        });
    }
    if (changeBtn) {
        changeBtn.addEventListener('click', function(e) {
            fileInput.click();
            options.classList.add('d-none');
        });
    }
    if (deleteBtn && deleteModal) {
        deleteBtn.addEventListener('click', function(e) {
            options.classList.add('d-none');
            deleteModal.show();
        });
    }
    // Hide options when clicking outside
    document.addEventListener('click', function(e) {
        if (options && !options.classList.contains('d-none') && !options.contains(e.target) && e.target !== editBtn) {
            options.classList.add('d-none');
        }
    });
  });
  </script>
</body>
</html>