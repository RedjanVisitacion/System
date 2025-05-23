<?php
require_once 'check_session.php';
require_once 'connection.php';

// Fetch user's profile picture and full name
$user_id = $_SESSION['user_id'];
$stmt = $con->prepare("SELECT profile_picture, full_name FROM elecom_user_profile WHERE user_id = ?");
$stmt->bind_param("s", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user_profile = $result->fetch_assoc();
$stmt->close();

// Check if the user has a profile picture, otherwise use a default icon
$profile_picture = (!empty($user_profile['profile_picture']) && file_exists('../uploads/profile_pictures/' . $user_profile['profile_picture']))
    ? '../uploads/profile_pictures/' . htmlspecialchars($user_profile['profile_picture'])
    : '../img/icon.png'; // Default icon path if no profile picture

// Optionally, you can also return the user's full name if needed
$full_name = $user_profile['full_name'] ?? 'Unknown User';



// Fetch candidate's photo only
$candidate_id = $_GET['candidate_id'] ?? null;
$candidate_photo = null;

if ($candidate_id) {
    $stmt = $con->prepare("SELECT photo FROM elecom_candidate WHERE candidate_id = ?");
    $stmt->bind_param("i", $candidate_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $candidate = $result->fetch_assoc();
    $stmt->close();

    // Fetch the filename from the database directly
    $photo_filename = $candidate['photo'] ?? null;

    // If the photo filename exists and it's not empty, use it to display the photo
    if (!empty($photo_filename)) {
        // Set the photo path for the image (stored in profile_pictures directory)
        $candidate_photo = '../uploads/profile_pictures/' . htmlspecialchars($photo_filename);
    }
}









// ✅ Add Candidate Submission Logic
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['full_name'], $_POST['department'], $_POST['position'],$_POST['age'], $_POST['age'], $_POST['info'], $_FILES['profile_pic'])) {
    header('Content-Type: application/json');

    $name = trim($_POST['full_name']);
    $department = trim($_POST['department']);
    $position = ucwords(strtolower(trim($_POST['position']))); // Capitalize only the position
    $platform = trim($_POST['info']);
    $age = filter_var($_POST['age'], FILTER_VALIDATE_INT);

    // Profile Picture Handling (same process as for the candidate)
    $photo = $_FILES['profile_pic'];
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif']; // Allowed file types
    $uploadDirectory = '../uploads/profile_pictures'; // Directory where the profile pictures will be stored

    // Check if the file is an allowed image type
    if (!in_array($photo['type'], $allowedTypes)) {
        echo json_encode(['success' => false, 'message' => 'Invalid file type. Only JPEG, PNG, and GIF are allowed.']);
        exit;
    }

    // Generate a unique filename for the image
    $photoPath = $uploadDirectory . time() . '_' . basename($photo['name']);

    // Move the uploaded file to the target directory
    if (!move_uploaded_file($photo['tmp_name'], $photoPath)) {
        echo json_encode(['success' => false, 'message' => 'Failed to upload the profile picture.']);
        exit;
    }

    // Check if any of the fields are empty
    if ($name === '' || $department === '' || $position === '' || $platform === '') {
        echo json_encode(['success' => false, 'message' => 'All fields are required.']);
        exit;
    }

      $age = (int) $_POST['age'];
      if ($age <= 0) {
          echo json_encode(['success' => false, 'message' => 'Invalid age provided.']);
          exit;
      }


    // Check if the candidate already exists
    $check_stmt = $con->prepare("SELECT candidate_id FROM elecom_candidate WHERE name = ? AND department = ? AND position = ?");
    $check_stmt->bind_param("sss", $name, $department, $position);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    $age = filter_var($_POST['age'], FILTER_VALIDATE_INT);

    if ($check_result->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Candidate with this name, department, and position already exists.']);
        exit;
    }

    // Insert candidate data along with profile picture into the database
    $stmt = $con->prepare("INSERT INTO elecom_candidate (name, department, position,age, platform, photo) VALUES (?, ?, ?, ?, ?, ?)");
    if ($stmt) {
        $stmt->bind_param("ssssss", $name, $department, $position,$age, $platform, $photoPath);
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


// Fetch and display candidate details (with photo)
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['candidate_id'])) {
  $candidate_id = $_GET['candidate_id'];

  // Fetch candidate details including photo and age
  $stmt = $con->prepare("SELECT name, department, position, age, platform, photo FROM elecom_candidate WHERE candidate_id = ?");
  $stmt->bind_param("i", $candidate_id);
  $stmt->execute();
  $result = $stmt->get_result();
  $candidate = $result->fetch_assoc();
  $stmt->close();

  if ($candidate) {
      $photoPath = !empty($candidate['photo']) && file_exists($candidate['photo'])
          ? $candidate['photo']
          : 'path/to/default/photo.png';  // Default photo

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




?>
<!DOCTYPE html>
<html lang="en">
<head>


<!-- Include Day.js + plugin -->
<script src="https://cdn.jsdelivr.net/npm/dayjs@1/dayjs.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/dayjs@1/plugin/customParseFormat.js"></script>
<script>
  dayjs.extend(dayjs_plugin_customParseFormat);
</script>

<!-- Your external JS -->
<script src="js/election_dates.js"></script>


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




    /* iPhone SE / older phones */
@media (max-width: 320px) {
  .mobile-menu-btn {
    display: block;
    margin-left: 50px;
  }
}

/* iPhone 6/7/8 */
@media (max-width: 375px) {
  .mobile-menu-btn {
    display: block;
    margin-left: 70px;
  }
}

/* iPhone XR / Pixel 3 XL */
@media (max-width: 414px) {
  .mobile-menu-btn {
    display: block;
    margin-left: 80px;
  }
}

/* Galaxy S8/S9 */
@media (max-width: 360px) {
  .mobile-menu-btn {
    display: block;
    margin-left: 65px;
  }
}

/* Pixel 5 */
@media (max-width: 393px) {
  .mobile-menu-btn {
    display: block;
    margin-left: 75px;
  }
}

/* iPhone 12 Pro */
@media (max-width: 390px) {
  .mobile-menu-btn {
    display: block;
    margin-left: 73px;
  }
}

/* OnePlus 9 */
@media (max-width: 412px) {
  .mobile-menu-btn {
    display: block;
    margin-left: 85px;
  }
}

/* iPhone 13 Pro Max */
@media (max-width: 430px) {
  .mobile-menu-btn {
    display: block;
    margin-left: 90px;
  }
}

/* Samsung Galaxy S20 Ultra */
@media (max-width: 440px) {
  .mobile-menu-btn {
    display: block;
    margin-left: 92px;
  }
}

/* Generic medium phones */
@media (max-width: 460px) {
  .mobile-menu-btn {
    display: block;
    margin-left: 100px;
  }
}

/* Large phones */
@media (max-width: 480px) {
  .mobile-menu-btn {
    display: block;
    margin-left: 105px;
  }
}

/* Small phablets */
@media (max-width: 500px) {
  .mobile-menu-btn {
    display: block;
    margin-left: 110px;
  }
}

/* Small tablets */
@media (max-width: 540px) {
  .mobile-menu-btn {
    display: block;
    margin-left: 115px;
  }
}

@media (max-width: 568px) {
  .mobile-menu-btn {
    display: block;
    margin-left: 118px;
  }
}

@media (max-width: 600px) {
  .mobile-menu-btn {
    display: block;
    margin-left: 120px;
  }
}

@media (max-width: 640px) {
  .mobile-menu-btn {
    display: block;
    margin-left: 125px;
  }
}

@media (max-width: 667px) {
  .mobile-menu-btn {
    display: block;
    margin-left: 128px;
  }
}

@media (max-width: 720px) {
  .mobile-menu-btn {
    display: block;
    margin-left: 130px;
  }
}

@media (max-width: 768px) {
  .mobile-menu-btn {
    display: block;
    margin-left: 135px;
  }
}

@media (max-width: 820px) {
  .mobile-menu-btn {
    display: block;
    margin-left: 140px;
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
        left: 30;
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

    tr:hover {
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

  </style>
  <style>
    .voted-user-item {
      padding: 8px 12px;
      border-bottom: 1px solid #e5e7eb;
      transition: background-color 0.2s;
    }
    
    .voted-user-item:last-child {
      border-bottom: none;
    }
    
    .voted-user-item:hover {
      background-color: #f8fafc;
    }
    
    .voted-user-item small {
      font-size: 0.75rem;
      display: block;
      margin-top: 2px;
    }
    
    #votedUsersList::-webkit-scrollbar {
      width: 6px;
    }
    
    #votedUsersList::-webkit-scrollbar-track {
      background: #f1f1f1;
      border-radius: 3px;
    }
    
    #votedUsersList::-webkit-scrollbar-thumb {
      background: #c1c1c1;
      border-radius: 3px;
    }
    
    #votedUsersList::-webkit-scrollbar-thumb:hover {
      background: #a8a8a8;
    }
  </style>
</head>
<body>
    <!-- Smoke Effect Canvas -->
    <canvas id="smoke-canvas" style="position:fixed;top:0;left:0;width:100vw;height:100vh;z-index:0;pointer-events:none;"></canvas>
    <!-- Floating Dragon -->
    <!-- <img id="dragon-float" src="../img/dragon.gif"  style="position:fixed;top:80px;left:0;width:50px;height:auto;z-index:10;pointer-events:none;opacity:0.45;transition:filter 0.3s;filter:drop-shadow(0 8px 16px rgba(0,0,0,0.18));">
    <img id="dragon-floatz" src="../img/dragon.gif"  style="position:fixed;top:80px;left:0;width:500px;height:auto;z-index:10;pointer-events:none;opacity:0.95;transition:filter 0.3s;filter:drop-shadow(0 8px 16px rgba(0,0,0,0.18));">-->
    
     <audio src="assets/welcomeBG.mp3" autoplay hidden></audio>
      <!--<audio src="assets/studentBG.mp3" autoplay loop hidden></audio>-->
    <!-- Top Bar -->
  
  <nav class="navbar navbar-expand-lg position-sticky" style="background: linear-gradient(90deg, rgb(26, 57, 119), rgb(72, 74, 80)); height: 60px;">
    <div class="container-fluid px-2">
      <div class="d-flex align-items-center justify-content-between w-100">
        <div class="d-flex align-items-center">
          <div class="dropdown">
            <a href="#" class="btn btn-link text-light d-lg-none p-0 d-flex align-items-center gap-2" style="font-size: 1.5rem; text-decoration: none;" role="button" id="mobileProfileDropdown" data-bs-toggle="dropdown" aria-expanded="false">
              <?php if ($profile_picture): ?>
                <img src="<?php echo $profile_picture; ?>" alt="use data to see photos" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover; border: 2px solid #fff;">
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

       <!-- Notification Icon -->
        <!-- Message Icon -->
       <!-- <a href="messages.php" class="notification-icon position-relative me-3 d-none d-lg-inline-flex" title="Messages">
          <i class="bi bi-chat" style="font-size: 1.2rem; color: #fff;"></i>
        </a> -->

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
            <a class="nav-link text-white d-flex align-items-center active" href="dashboard_officer.php">
              <i class="bi bi-house-door"></i>
              <span class="sidebar-text">Home</span>
            </a>
          </li>
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
            <a class="nav-link text-white d-flex align-items-center" href="#" data-bs-toggle="modal" data-bs-target="#addCandidateModal" onclick="if(window.innerWidth <= 991.98){document.getElementById('sidebar').classList.remove('active');document.getElementById('sidebarOverlay').classList.remove('active');document.getElementById('mobileMenuBtn').classList.remove('active');}">
              <i class="bi bi-plus-circle"></i>
              <span class="sidebar-text">Add Candidate</span>
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link text-white d-flex align-items-center" href="#" data-bs-toggle="modal" data-bs-target="#removeCandidateModal"
                onclick="if(window.innerWidth <= 991.98){
                    document.getElementById('sidebar').classList.remove('active');
                    document.getElementById('sidebarOverlay').classList.remove('active');
                    document.getElementById('mobileMenuBtn').classList.remove('active');
                }">
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
                <div class="dashboard-card p-4" id="totalCandidatesCard" style="cursor: pointer;">
                  <div class="icon">
                    <i class="bi bi-people"></i>
                  </div>
                  <h3 class="fw-bold text-center">Total Candidates</h3>
                  <p class="fs-4 text-center mb-0" id="totalCandidates">0</p>
                </div>
              </div>
              <!-- Total Voters Dashboard Card -->
              <div class="col-12 col-md-6">
                <div class="dashboard-card p-4" id="totalVotersCard" style="cursor: pointer;">
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
                    <i class="bi bi-person-check"></i>
                  </div>
                  <h3 class="fw-bold text-center">Votes Cast</h3>
                  <div id="resetVotesContainer1" class="mb-2" style="display:none;">
                    <button class="btn btn-danger btn-sm w-100" id="resetVotesBtn1">
                      <i class="bi bi-arrow-counterclockwise"></i> Reset All Votes
                    </button>
                  </div>
                  <p class="fs-4 text-center mb-0" id="totalVotesCast">0</p>
                </div>
              </div>
              <div class="col-12 col-md-6">
                <div class="dashboard-card p-4">
                  <div class="icon">
                    <i class="bi bi-clock"></i>
                  </div>
                  <h3 class="fw-bold text-center">Time Remaining</h3>
                  <div class="card-body">
                    <p class="fs-4 text-center mb-0" id="timeRemaining">Loading...</p>
                  </div>
                </div>
              </div>
              <div class="col-12">
                <div class="dashboard-card p-4">
                  <div class="icon">
                    <i class="bi bi-person-check"></i>
                  </div>
                  <h3 class="fw-bold text-center">Voted Students</h3>
                  <div id="resetVotesContainer2" class="mb-2" style="display:block;">
                    <button class="btn btn-danger btn-sm w-100" id="resetVotesBtn2">
                      <i class="bi bi-arrow-counterclockwise"></i> Reset All Votes
                    </button>
                  </div>
                  <div id="votedUsersList" class="mt-3" style="max-height: 200px; overflow-y: auto;">
                    <div class="text-center">
                      <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                      </div>
                    </div>
                  </div>
                </div>
              </div>


            </div>
          </div>
          <!-- Calendar Card (desktop only) -->
          <div class="col-12 col-lg-4 d-none d-lg-block">
            <div class="calendar-card">
              <h5 class="mb-3">Set Election Dates</h5>
              
              <div class="mb-3">
                <label for="startDate" class="form-label">Start Date & Time</label>
                <input type="datetime-local" id="startDate" class="form-control" required>
              </div>
              <div class="mb-3">
                <label for="endDate" class="form-label">End Date & Time</label>
                <input type="datetime-local" id="endDate" class="form-control" required>
              </div>
              <div class="mb-3">
                <label for="resultsDate" class="form-label">Results Date & Time</label>
                <input type="datetime-local" id="resultsDate" class="form-control" required>
              </div>
              
              <button class="btn btn-primary" onclick="setElectionDates()">Save Dates</button>
            </div>
          </div>
          <!-- Election Timeline Card for Mobile (visible only on mobile/tablet) -->
          <div class="col-12 d-block d-lg-none mb-3">
            <div class="calendar-card">
              <h5 class="mb-3">Set Election Dates</h5>
              <div class="mb-3">
                <label for="startDateMobile" class="form-label">Start Date & Time</label>
                <input type="datetime-local" id="startDateMobile" class="form-control" required>
              </div>
              <div class="mb-3">
                <label for="endDateMobile" class="form-label">End Date & Time</label>
                <input type="datetime-local" id="endDateMobile" class="form-control" required>
              </div>
              <div class="mb-3">
                <label for="resultsDateMobile" class="form-label">Results Date & Time</label>
                <input type="datetime-local" id="resultsDateMobile" class="form-control" required>
              </div>
              <button class="btn btn-primary" onclick="setElectionDatesMobile()">Save Dates</button>
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
        <form id="addCandidateForm" enctype="multipart/form-data">
          <input type="hidden" id="candidate_id" name="candidate_id">
          <div class="row g-3">
            <div class="col-12 col-md-6">
              <label for="full_name" class="form-label">Full Name</label>
              <input type="text" class="form-control" id="full_name" name="full_name" required>
            </div>
            <div class="col-12 col-md-6">
              <label for="age" class="form-label">Age</label>
              <input type="number" class="form-control" id="age" name="age" min="1" required>
            </div>

            <div class="col-12 col-md">
              <label for="department" class="form-label">Department</label>
              <select class="form-select" id="department" name="department" required>
                <option value="">Select Department</option>
                <option value="USG">USG</option>
                <option value="SITE">BSIT (SITE Officers)</option>
                <option value="AFPROTECHS">BFPT (AFPROTECHS)</option>
                <option value="PAFE">BTLED (PAFE)</option>
              </select>
            </div>
            <div class="col-12 col-md-6">
              <label for="candidatePosition" class="form-label">Position</label>
              <select class="form-select" id="candidatePosition" name="position" required>
                <option value="">Select Position</option>
              </select>
            </div>
            <div class="col-12">
              <label for="profile_pic" class="form-label">Profile Picture</label>
              <input type="file" class="form-control" id="profile_pic" name="profile_pic" accept="image/*">
              <div id="currentPhoto" class="mt-2 d-none">
                <label class="form-label">Current Photo:</label>
                <img id="currentPhotoPreview" src="" alt="Current Photo" style="max-width: 150px; max-height: 150px;">
              </div>
            </div>

            <div class="col-12">
              <label for="info" class="form-label">Candidate Information</label>
              <textarea class="form-control" id="info" name="info" rows="3" placeholder="Brief background or platform..." required></textarea>
            </div>
          </div>
          <button type="submit" class="btn btn-primary w-100 mt-3" id="submitBtn">Add Candidate</button>
        </form>

        <div id="addCandidateMsg" class="mt-2"></div>
      </div>
    </div>
  </div>
</div>



<!-- Voters Modal -->
<div class="modal fade" id="votersModal" tabindex="-1" aria-labelledby="votersModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="votersModalLabel">All Voters</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <label for="voterSearchInput" class="form-label">Search Voters</label>
          <input type="text" class="form-control" id="voterSearchInput" placeholder="Search by full name...">
        </div>

        <!-- Scrollable Table -->
        <div style="max-height: 400px; overflow-y: auto;">
          <table class="table table-bordered align-middle">
            <thead class="table-light">
              <tr>
                <th>Full Name</th> <!-- Removed profile picture column -->
              </tr>
            </thead>
            <tbody id="voterTableBody">
              <!-- Filled dynamically -->
            </tbody>
          </table>
        </div>
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
          <input type="hidden" name="candidate_id" id="selectedCandidateId">
          
          <!-- Search Input -->
          <div class="mb-3">
            <label for="searchCandidate" class="form-label">Search by Name</label>
            <input type="text" class="form-control" id="searchCandidate" placeholder="Search Candidate">
          </div>

          <!-- Table with scrollable body -->
          <div style="max-height: 300px; overflow-y: auto;">
            <table class="table table-bordered table-hover" id="candidateTable">
              <thead class="table-light" style="position: sticky; top: 0; background-color: white; z-index: 1;">
                <tr>
                  <th><input type="checkbox" id="selectAllCheckbox"> Select All</th>
                  <th>Name</th>
                  <th>Position</th>
                  <th>Department</th>
                </tr>
              </thead>
              <tbody>
                <!-- Rows will be populated dynamically -->
              </tbody>
            </table>
          </div>

          <button type="submit" class="btn btn-danger w-100 mt-3" id="removeBtn" disabled>Remove Selected Candidates</button>
        </form>
        <div id="removeCandidateMsg" class="mt-2"></div>
      </div>
    </div>
  </div>
</div>


<div class="modal fade" id="viewCandidatesModal" tabindex="-1" aria-labelledby="viewCandidatesModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="viewCandidatesModalLabel">All Candidates</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <label for="searchViewCandidate" class="form-label">Search by Name</label>
          <input type="text" class="form-control" id="searchViewCandidate" placeholder="Search Candidate">
        </div>
        <div style="max-height: 300px; overflow-y: auto;">
          <table class="table table-bordered" id="viewCandidateTable">
            <thead class="table-light">
              <tr>
                <th>Candidate ID</th>
                <th>Name</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              <!-- Candidate rows go here -->
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


 


<!-- View Candidates Modal -->
<div class="modal fade" id="viewCandidatesModal" tabindex="-1" aria-labelledby="viewCandidatesModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="viewCandidatesModalLabel">View Candidates</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <!-- Search input for candidates -->
        <input type="text" class="form-control" id="searchViewCandidate" placeholder="Search candidates...">
        <div class="mt-3" id="viewCandidateMsg"></div>

        <!-- Dynamic container to group and display candidates -->
        <div id="candidateGroups" class="mt-3">
          <!-- Groups of candidates by department or organization will be rendered here -->
        </div>
      </div>
    </div>
  </div>
</div>



<!-- Voter Profile Modal -->
<div class="modal fade" id="voterProfileModal" tabindex="-1" aria-labelledby="voterProfileModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content p-3">
      <div class="modal-header d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center gap-2">
          <img id="voterPhoto" class="rounded-circle" alt="Voter Photo"
               style="width: 40px; height: 40px; object-fit: cover; border: 2px solid #fff;">
          <h5 class="modal-title mb-0" id="voterProfileModalLabel">Voter Name</h5>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div id="voterProfileCard" class="card shadow rounded-4 border-0">
          <div class="card-body">
            <p class="mb-2"><strong>Student ID:</strong> <span id="voterId"></span></p>
            <p class="mb-2"><strong>Section:</strong> <span id="voterSection"></span></p>
            <p class="mb-2"><strong>Program:</strong> <span id="voterProgram"></span></p>
            <p class="mb-2"><strong>Gender:</strong> <span id="voterGender"></span></p>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>





  <script>
 const departmentSelect = document.getElementById("department");
const positionSelect = document.getElementById("candidatePosition");

const basePositions = [
  "President",
  "Vice-President",
  "General Secretary",
  "Associate Secretary",
  "Treasurer",
  "Auditor",
  "PIO"
];

const usgRepresentatives = [
  "BTLED Representative",
  "BSIT Representative",
  "BFPT Representative"
];

// Define positions for each department
const positions = {
  'USG': [
    'President',
    'Vice President',
    'General Secretary',
    'Associate Secretary',
    'Treasurer',
    'Auditor',
    'Public Information Officer',
    'BTLED Representatives',
    'BSIT Representatives',
    'BFPT Representatives'
  ],
  'AFPROTECHS': [
    'President',
    'Vice President',
    'General Secretary',
    'Associate Secretary',
    'Treasurer',
    'Auditor',
    'Public Information Officer'
  ],
  'SITE': [
    'President',
    'Vice President',
    'General Secretary',
    'Associate Secretary',
    'Treasurer',
    'Auditor',
    'Public Information Officer'
  ],
  'PAFE': [
    'President',
    'Vice President',
    'General Secretary',
    'Associate Secretary',
    'Treasurer',
    'Auditor',
    'Public Information Officer'
  ]
};

// Function to update position options based on selected department
document.getElementById('department').addEventListener('change', function() {
  const dept = this.value;
  const positionSelect = document.getElementById('candidatePosition');
  positionSelect.innerHTML = '<option value="">Select Position</option>';
  
  if (dept && positions[dept]) {
    positions[dept].forEach(pos => {
      const option = document.createElement('option');
      option.value = pos;
      option.textContent = pos;
      positionSelect.appendChild(option);
    });
  }
});

// Function to check if a position is a representative position
function isRepresentativePosition(position) {
  return position.includes('Representatives');
}

// Populate positions based on selected department
departmentSelect.addEventListener("change", () => {
  const dept = departmentSelect.value;
  positionSelect.innerHTML = `<option value="">Select Position</option>`;

  if (dept) {
    // Add base positions for any department
    basePositions.forEach(pos => {
      const option = document.createElement("option");
      option.value = pos; // Keep original casing
      option.textContent = pos;
      positionSelect.appendChild(option);
    });

    // Add representative positions only for USG
    if (dept === "USG") {
      usgRepresentatives.forEach(rep => {
        const option = document.createElement("option");
        option.value = rep; // Keep original casing
        option.textContent = rep;
        positionSelect.appendChild(option);
      });
    }
  }
});

// Handle form submission
document.getElementById('addCandidateForm').onsubmit = function (e) {
  e.preventDefault();

  const form = this;
  const msg = document.getElementById('addCandidateMsg');
  const positionSelect = document.getElementById('positionSelect'); // Make sure this exists in your HTML

  // Clear previous messages
  msg.textContent = '';
  msg.className = '';

  const formData = new FormData(form);

  fetch('dashboard_officer.php', {
    method: 'POST',
    body: formData
  })
    .then(r => r.json())
    .then(response => {
      if (response.success) {
        msg.textContent = response.message || 'Candidate added successfully!';
        msg.className = 'text-success mt-2';
        form.reset(); // Clear form on success
        if (positionSelect) {
          positionSelect.innerHTML = '<option value="">Select Position</option>'; // Reset positions
        }
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

// Reset message when modal is closed (assuming Bootstrap modal with ID 'addCandidateModal')
document.getElementById('addCandidateModal')?.addEventListener('hidden.bs.modal', function () {
  const msg = document.getElementById('addCandidateMsg');
  if (msg) {
    msg.textContent = '';
    msg.className = '';
  }

  // Optional: also reset form and position dropdown when modal is closed
  const form = document.getElementById('addCandidateForm');
  form?.reset();

  const positionSelect = document.getElementById('positionSelect');
  if (positionSelect) {
    positionSelect.innerHTML = '<option value="">Select Position</option>';
  }
});




// DOM Elements
const votersModalElement = document.getElementById('votersModal');
const votersModal = new bootstrap.Modal(votersModalElement);
const voterTableBody = document.getElementById('voterTableBody');
const voterSearchInput = document.getElementById('voterSearchInput');
const totalVotersCard = document.getElementById('totalVotersCard');
const totalVotersCount = document.getElementById('totalVoters');

let allVoters = [];
let filteredVoters = [];

function loadVotersTable(searchQuery = '') {
  voterTableBody.innerHTML = '<tr><td>Loading...</td></tr>';

  fetch('fetch_voters.php')
    .then(response => response.json())
    .then(data => {
      if (data.success && Array.isArray(data.voters)) {
        allVoters = data.voters;
        totalVotersCount.textContent = allVoters.length;
        renderVoters(allVoters, searchQuery);
      } else {
        voterTableBody.innerHTML = '<tr><td>No voters found.</td></tr>';
      }
    })
    .catch(error => {
      console.error('Error fetching voters:', error);
      voterTableBody.innerHTML = `<tr><td>Error loading voters: ${error.message}</td></tr>`;
    });
}

function renderVoters(voters, searchQuery = '') {
  filteredVoters = voters.filter(voter =>
    voter.full_name.toLowerCase().includes(searchQuery.toLowerCase())
  );

  voterTableBody.innerHTML = filteredVoters.length
    ? filteredVoters.map((voter, index) => `
      <tr style="cursor: pointer;" onclick="showVoterProfile(${index})">
        <td>${voter.full_name}</td>
      </tr>
    `).join('')
    : '<tr><td>No matching voters found.</td></tr>';
}

function showVoterProfile(index) {
  const voter = filteredVoters[index];
  if (!voter) return;

  document.getElementById('voterProfileModalLabel').textContent = voter.full_name || 'Voter Profile';
  document.getElementById('voterId').textContent = voter.user_id || 'N/A';
  document.getElementById('voterSection').textContent = voter.section_name || 'N/A';
  document.getElementById('voterProgram').textContent = voter.program_name || 'N/A';
  document.getElementById('voterGender').textContent = voter.gender || 'N/A';

  const photoElement = document.getElementById('voterPhoto');
  // Set voter photo
  if (voter.profile_picture) {
    // The photo path from database already includes '../uploads/profile_pictures/'
    photoElement.src = voter.profile_picture;
  } else {
    photoElement.src = '../img/icon.png';
  }

  const votersModalInstance = bootstrap.Modal.getInstance(votersModalElement);
  if (votersModalInstance) {
    votersModalInstance.hide();
  }

  const profileModal = new bootstrap.Modal(document.getElementById('voterProfileModal'));
  profileModal.show();
}

// Triggers
totalVotersCard.addEventListener('click', () => {
  votersModal.show();
  loadVotersTable();
});

voterSearchInput.addEventListener('input', (e) => {
  renderVoters(allVoters, e.target.value);
});




// Remove Candidate Modal Setup
const removeCandidateModal = document.getElementById('removeCandidateModal');
const tableBody = document.querySelector('#candidateTable tbody');
const selectedInput = document.getElementById('selectedCandidateId');
const removeBtn = document.getElementById('removeBtn');
const msg = document.getElementById('removeCandidateMsg');
const searchInput = document.getElementById('searchCandidate');
const selectAllCheckbox = document.getElementById('selectAllCheckbox');

// Fetch and render candidates
function loadCandidateTable(searchQuery = '') {
  tableBody.innerHTML = '<tr><td colspan="4">Loading...</td></tr>';
  selectedInput.value = '';
  removeBtn.disabled = true;
  msg.textContent = '';
  msg.className = '';

  fetch('fetch_candidates.php')
    .then(response => response.json())
    .then(data => {
      if (data.success && Array.isArray(data.candidates) && data.candidates.length > 0) {
        tableBody.innerHTML = '';
        data.candidates.forEach(candidate => {
          if (!candidate.name.toLowerCase().includes(searchQuery.toLowerCase())) return;

          const row = document.createElement('tr');
          row.innerHTML = `
            <td><input type="checkbox" class="candidateCheckbox" data-id="${candidate.candidate_id}"></td>
            <td>${candidate.name}</td>
            <td>${candidate.position}</td>
            <td>${candidate.department}</td>
          `;
          tableBody.appendChild(row);
        });

        document.querySelectorAll('.candidateCheckbox').forEach(checkbox => {
          checkbox.addEventListener('change', updateSelectedCandidates);
        });
      } else {
        tableBody.innerHTML = '<tr><td colspan="4">No candidates found</td></tr>';
      }
    })
    .catch(error => {
      console.error('Error fetching candidates:', error);
      tableBody.innerHTML = '<tr><td colspan="4">Error loading candidates</td></tr>';
    });
}

// Reload candidates when modal is shown
removeCandidateModal.addEventListener('shown.bs.modal', () => loadCandidateTable());

// Handle search input
searchInput.addEventListener('input', (e) => {
  loadCandidateTable(e.target.value);
});

// Select All candidates
selectAllCheckbox.addEventListener('change', () => {
  const checkboxes = document.querySelectorAll('.candidateCheckbox');
  checkboxes.forEach(checkbox => {
    checkbox.checked = selectAllCheckbox.checked;
  });
  updateSelectedCandidates();
});

// Update selected candidates and enable/remove the remove button
function updateSelectedCandidates() {
  const selectedCandidates = [];
  document.querySelectorAll('.candidateCheckbox:checked').forEach(checkbox => {
    selectedCandidates.push(checkbox.getAttribute('data-id'));
  });

  selectedInput.value = JSON.stringify(selectedCandidates); // Store as JSON array
  removeBtn.disabled = selectedCandidates.length === 0;
}

// Handle Candidate Removal
document.getElementById('removeCandidateForm').onsubmit = function (e) {
  e.preventDefault();

  msg.textContent = '';
  msg.className = '';
  removeBtn.disabled = true;

  let selectedIds;
  try {
    selectedIds = JSON.parse(selectedInput.value || '[]');
  } catch (error) {
    msg.textContent = 'Invalid selection.';
    msg.className = 'text-danger mt-2';
    removeBtn.disabled = false;
    return;
  }

  if (selectedIds.length === 0) {
    msg.textContent = 'No candidates selected.';
    msg.className = 'text-danger mt-2';
    return;
  }

  const formData = new FormData();
  selectedIds.forEach(id => formData.append('candidate_id[]', id));

  fetch('remove_candidate.php', {
    method: 'POST',
    body: formData
  })
    .then(r => r.json())
    .then(response => {
      if (response.success) {
        msg.textContent = response.message || 'Candidates removed successfully!';
        msg.className = 'text-success mt-2';
        this.reset();
        selectedInput.value = '';
        selectAllCheckbox.checked = false;
        loadCandidateTable();
      } else {
        msg.textContent = response.message || 'Error removing candidate(s).';
        msg.className = 'text-danger mt-2';
        removeBtn.disabled = false;
      }
    })
    .catch(() => {
      msg.textContent = 'Error connecting to server.';
      msg.className = 'text-danger mt-2';
      removeBtn.disabled = false;
    });
};




//Show all Candidates
const viewCandidateModal = document.getElementById('viewCandidatesModal');
const viewTableBody = document.querySelector('#viewCandidateTable tbody');
const viewMsg = document.getElementById('viewCandidateMsg');
const viewSearchInput = document.getElementById('searchViewCandidate');

// Fetch and render candidates
function loadViewCandidateTable(searchQuery = '') {
  viewTableBody.innerHTML = '<tr><td colspan="3">Loading...</td></tr>';
  viewMsg.textContent = '';
  viewMsg.className = '';

  fetch('fetch_candidates.php')
    .then(response => response.json())
    .then(data => {
      if (data.success && Array.isArray(data.candidates) && data.candidates.length > 0) {
        viewTableBody.innerHTML = '';
        let hasMatch = false;

        data.candidates.forEach(candidate => {
          if (!candidate.name.toLowerCase().includes(searchQuery.toLowerCase())) return;

          const row = document.createElement('tr');
          row.style.cursor = 'pointer';
          row.innerHTML = `
            <td>${candidate.candidate_id}</td>
            <td onclick="showCandidateProfile(${JSON.stringify(candidate).replace(/"/g, '&quot;')})">${candidate.name}</td>
            <td>
              <button class="btn btn-sm btn-primary" onclick="event.stopPropagation(); editCandidate(${candidate.candidate_id})">
                <i class="bi bi-pencil"></i> Edit
              </button>
            </td>
          `;
          viewTableBody.appendChild(row);
          hasMatch = true;
        });

        if (!hasMatch) {
          viewTableBody.innerHTML = '<tr><td colspan="3">No matching candidates found.</td></tr>';
        }
      } else {
        viewTableBody.innerHTML = '<tr><td colspan="3">No candidates found.</td></tr>';
      }
    })
    .catch(error => {
      console.error('Error fetching candidates:', error);
      viewTableBody.innerHTML = '<tr><td colspan="3">Error loading candidates.</td></tr>';
    });
}

// Load when modal is opened
viewCandidateModal.addEventListener('shown.bs.modal', () => loadViewCandidateTable());

// Live search
viewSearchInput.addEventListener('input', (e) => {
  loadViewCandidateTable(e.target.value);
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
    // The photo path from database already includes '../uploads/profile_pictures/'
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

// Requires dayjs + customParseFormat plugin loaded in HTML

document.addEventListener('DOMContentLoaded', () => {
  fetchElectionDatesAndStartCountdown();
  loadElectionDatesIntoInputs();
});

// Save election dates
function setElectionDates() {
  const startDateRaw = document.getElementById('startDate').value;
  const endDateRaw = document.getElementById('endDate').value;
  const resultsDateRaw = document.getElementById('resultsDate').value;

  if (!startDateRaw || !endDateRaw || !resultsDateRaw) {
    alert('Please fill in all date fields.');
    return;
  }

  const startDate = dayjs(startDateRaw).format('YYYY-MM-DD HH:mm:ss');
  const endDate = dayjs(endDateRaw).format('YYYY-MM-DD HH:mm:ss');
  const resultsDate = dayjs(resultsDateRaw).format('YYYY-MM-DD HH:mm:ss');

  fetch('../src/get_election_dates.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      action: 'update_dates',
      start_date: startDate,
      end_date: endDate,
      results_date: resultsDate
    })
  })
  .then(response => response.json())
  .then(data => {
    alert(data.message);
    if (data.success && typeof updateElectionDates === 'function') {
      updateElectionDates();
    }
  })
  .catch(error => {
    console.error('Error updating dates:', error);
    alert('Something went wrong while updating the dates.');
  });
}

// Load dates into input fields
function loadElectionDatesIntoInputs() {
  fetch('../src/get_election_dates.php')
    .then(res => res.json())
    .then(data => {
      if (data.success) {
        document.getElementById('startDate').value = dayjs(data.start_date).format('YYYY-MM-DDTHH:mm');
        document.getElementById('endDate').value = dayjs(data.end_date).format('YYYY-MM-DDTHH:mm');
        document.getElementById('resultsDate').value = dayjs(data.results_date).format('YYYY-MM-DDTHH:mm');
      }
    });
}

// Countdown handling
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
      timeEl.style.color = '';
    } else if (now >= startTime && now < endTime) {
      diff = endTime - now;
      label = 'Voting ends in ';
      timeEl.style.color = '';

      const minutesRemaining = Math.floor(diff / (1000 * 60));
      if (minutesRemaining === 1) {
        const audio = document.getElementById('countdownAudio');
        if (audio) audio.play();
      }
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

  // Add click handler for total candidates card
  document.getElementById('totalCandidatesCard').addEventListener('click', () => {
    const viewCandidatesModal = new bootstrap.Modal(document.getElementById('viewCandidatesModal'));
    viewCandidatesModal.show();
    loadViewCandidateTable();
  });

  // Function to edit candidate
  function editCandidate(candidateId) {
    // Close the view candidates modal first
    const viewModal = bootstrap.Modal.getInstance(document.getElementById('viewCandidatesModal'));
    if (viewModal) {
      viewModal.hide();
    }

    // Fetch candidate details
    fetch(`get_candidate.php?candidate_id=${candidateId}`)
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          const candidate = data.candidate;
          
          // Update modal title and button
          document.getElementById('addCandidateModalLabel').textContent = 'Edit Candidate';
          document.getElementById('submitBtn').textContent = 'Update Candidate';
          
          // Populate form fields
          document.getElementById('candidate_id').value = candidate.candidate_id;
          document.getElementById('full_name').value = candidate.name;
          document.getElementById('age').value = candidate.age;
          document.getElementById('department').value = candidate.department;
          document.getElementById('info').value = candidate.platform;
          
          // Handle position selection
          const positionSelect = document.getElementById('candidatePosition');
          positionSelect.innerHTML = '<option value="">Select Position</option>';
          
          if (candidate.department && positions[candidate.department]) {
            positions[candidate.department].forEach(pos => {
              const option = document.createElement('option');
              option.value = pos;
              option.textContent = pos;
              if (pos === candidate.position) option.selected = true;
              positionSelect.appendChild(option);
            });
          }
          
          // Show current photo if exists
          const currentPhotoDiv = document.getElementById('currentPhoto');
          const currentPhotoPreview = document.getElementById('currentPhotoPreview');
          if (candidate.photo) {
            currentPhotoPreview.src = candidate.photo;
            currentPhotoDiv.classList.remove('d-none');
          } else {
            currentPhotoDiv.classList.add('d-none');
          }
          
          // Make profile picture optional for edit
          document.getElementById('profile_pic').required = false;
          
          // Show the edit modal
          const addCandidateModal = new bootstrap.Modal(document.getElementById('addCandidateModal'));
          addCandidateModal.show();
        }
      })
      .catch(error => {
        console.error('Error fetching candidate details:', error);
        alert('Error loading candidate details');
      });
  }

  // Update form submission to handle both add and edit
  document.getElementById('addCandidateForm').onsubmit = function(e) {
    e.preventDefault();
    
    const form = this;
    const msg = document.getElementById('addCandidateMsg');
    const candidateId = document.getElementById('candidate_id').value;
    const isEdit = candidateId !== '';
    
    msg.textContent = '';
    msg.className = '';
    
    const formData = new FormData(form);
    formData.append('action', isEdit ? 'edit' : 'add');
    
    fetch('manage_candidate.php', {
      method: 'POST',
      body: formData
    })
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        // Show success message
        const successAlert = document.createElement('div');
        successAlert.className = 'alert alert-success alert-dismissible fade show position-fixed top-50 start-50 translate-middle';
        successAlert.style.zIndex = '1500';
        successAlert.role = 'alert';
        successAlert.innerHTML = `
          ${data.message}
          <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        `;
        document.body.appendChild(successAlert);
        
        // Auto-dismiss the alert after 3 seconds
        setTimeout(() => {
          successAlert.classList.remove('show');
          setTimeout(() => successAlert.remove(), 150);
        }, 3000);
        
        msg.textContent = data.message;
        msg.className = 'text-success mt-2';
        
        // Reset form and close modal after success
        form.reset();
        document.getElementById('candidate_id').value = '';
        document.getElementById('currentPhoto').classList.add('d-none');
        document.getElementById('addCandidateModalLabel').textContent = 'Add Candidate';
        document.getElementById('submitBtn').textContent = 'Add Candidate';
        document.getElementById('profile_pic').required = true;
        
        // Close modal
        const modal = bootstrap.Modal.getInstance(document.getElementById('addCandidateModal'));
        modal.hide();
        
        // Refresh candidate list
        loadViewCandidateTable();
      } else {
        msg.textContent = data.message;
        msg.className = 'text-danger mt-2';
      }
    })
    .catch(error => {
      console.error('Error:', error);
      msg.textContent = 'Error processing request';
      msg.className = 'text-danger mt-2';
    });
  };

  // Reset form when modal is closed
  document.getElementById('addCandidateModal').addEventListener('hidden.bs.modal', function() {
    const form = document.getElementById('addCandidateForm');
    form.reset();
    document.getElementById('candidate_id').value = '';
    document.getElementById('currentPhoto').classList.add('d-none');
    document.getElementById('addCandidateModalLabel').textContent = 'Add Candidate';
    document.getElementById('submitBtn').textContent = 'Add Candidate';
    document.getElementById('profile_pic').required = true;
    document.getElementById('addCandidateMsg').textContent = '';
  });

  // Add this CSS to style the voted users list
  const style = document.createElement('style');
  style.textContent = `
    .voted-user-item {
      padding: 8px 12px;
      border-bottom: 1px solid #e5e7eb;
      transition: background-color 0.2s;
    }
    
    .voted-user-item:last-child {
      border-bottom: none;
    }
    
    .voted-user-item:hover {
      background-color: #f8fafc;
    }
    
    .voted-user-item small {
      font-size: 0.75rem;
      display: block;
      margin-top: 2px;
    }
    
    #votedUsersList::-webkit-scrollbar {
      width: 6px;
    }
    
    #votedUsersList::-webkit-scrollbar-track {
      background: #f1f1f1;
      border-radius: 3px;
    }
    
    #votedUsersList::-webkit-scrollbar-thumb {
      background: #c1c1c1;
      border-radius: 3px;
    }
    
    #votedUsersList::-webkit-scrollbar-thumb:hover {
      background: #a8a8a8;
    }
  `;
  document.head.appendChild(style);

  // Update the updateTotalVotesCast function
  function updateTotalVotesCast() {
    fetch('../src/get_total_votes_cast.php')
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          document.getElementById('totalVotesCast').textContent = data.total_votes;
          
          // Create or update the voted users list
          const votedUsersList = document.getElementById('votedUsersList');
          if (votedUsersList) {
            votedUsersList.innerHTML = `
              <div class="text-center mb-2">
                <small class="text-muted">Total Voted Students: ${data.voted_users.length}</small>
              </div>
              ${data.voted_users.map(user => `
                <div class="voted-user-item">
                  <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-person-check-fill text-success"></i>
                    <span>${user.full_name}</span>
                  </div>
                  <small class="text-muted">${user.program_name}</small>
                </div>
              `).join('')}
            `;
          }
        }
      })
      .catch(error => {
        console.error('Error fetching total votes cast:', error);
      });
  }

  // Make sure to call updateTotalVotesCast in your initialization
  document.addEventListener('DOMContentLoaded', function() {
    updateTotalVotesCast();
    // Update every 30 seconds
    setInterval(updateTotalVotesCast, 30000);
  });

  // Function to handle reset votes
  function handleResetVotes() {
    if (confirm('Are you sure you want to reset all votes? This action cannot be undone.')) {
      // Use the correct path to reset_votes.php
      fetch('reset_votes.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-Requested-With': 'XMLHttpRequest'
        },
        credentials: 'same-origin' // Add this to include cookies
      })
      .then(response => {
        if (!response.ok) {
          throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
      })
      .then(data => {
        if (data.success) {
          alert('All votes have been reset!');
          // Refresh dashboard stats
          if (typeof updateTotalVotesCast === 'function') {
            updateTotalVotesCast();
          }
          if (typeof updateTotalCandidates === 'function') {
            updateTotalCandidates();
          }
          if (typeof loadVotedUsers === 'function') {
            loadVotedUsers();
          }
        } else {
          alert(data.message || 'Failed to reset votes.');
        }
      })
      .catch(error => {
        console.error('Error:', error);
        alert('Error connecting to server. Please try again.');
      });
    }
  }

  // Add event listeners to both reset buttons
  document.addEventListener('DOMContentLoaded', function() {
    const resetBtn1 = document.getElementById('resetVotesBtn1');
    const resetBtn2 = document.getElementById('resetVotesBtn2');
    
    if (resetBtn1) {
      resetBtn1.addEventListener('click', handleResetVotes);
    }
    if (resetBtn2) {
      resetBtn2.addEventListener('click', handleResetVotes);
    }
  });

  // Update the display logic for reset containers
  document.querySelector('.dashboard-card:nth-child(3)').addEventListener('click', () => {
    const votedUsersList = document.getElementById('votedUsersList');
    const resetVotesContainer1 = document.getElementById('resetVotesContainer1');
    const resetVotesContainer2 = document.getElementById('resetVotesContainer2');
    
    if (votedUsersList) {
      votedUsersList.scrollIntoView({ behavior: 'smooth', block: 'start' });
      votedUsersList.style.transition = 'all 0.3s ease';
      votedUsersList.style.backgroundColor = '#f8fafc';
      votedUsersList.style.boxShadow = '0 0 0 2px #2563eb';
      setTimeout(() => {
        votedUsersList.style.backgroundColor = '';
        votedUsersList.style.boxShadow = '';
      }, 2000);
    }
    
    if (resetVotesContainer1) {
      resetVotesContainer1.style.display = 'block';
    }
    if (resetVotesContainer2) {
      resetVotesContainer2.style.display = 'block';
    }
  });
  </script>
  
  <script>
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
        'rgba(13,110,253,0.10)', // blue
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
  </script>
  <script>
    // Floating dragon animation
    (function() {
      const dragon = document.getElementById('dragon-float');
      if (!dragon) return;
      let direction = 1;
      let pos = 0;
      let max = window.innerWidth - 140;
      let min = 0;
      function animateDragon() {
        pos += direction * 1.2;
        if (pos > max) { direction = -2; dragon.style.transform = 'scaleX(-2)'; }
        if (pos < min) { direction = 2; dragon.style.transform = 'scaleX(2)'; }
        dragon.style.left = pos + 'px';
        requestAnimationFrame(animateDragon);
      }
      window.addEventListener('resize', () => { max = window.innerWidth - 350; });
      animateDragon();
   
    })();

    // Floating dragon animation
    (function() {
      const dragon = document.getElementById('dragon-floatz');
      if (!dragon) return;
      let direction = 3;
      let pos = 0;
      let max = window.innerWidth - 140;
      let min = 0;
      function animateDragon() {
        pos += direction * 1.2;
        if (pos > max) { direction = -2; dragon.style.transform = 'scaleX(-2)'; }
        if (pos < min) { direction = 2; dragon.style.transform = 'scaleX(2)'; }
        dragon.style.left = pos + 'px';
        requestAnimationFrame(animateDragon);
      }
      window.addEventListener('resize', () => { max = window.innerWidth - 350; });
      animateDragon();

    })();
  </script>
</body>
</html>


