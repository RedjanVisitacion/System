<?php
require_once 'connection.php';

// Query to count total candidates
$query = "SELECT COUNT(*) as total_candidates FROM elecom_candidate";
$result = $con->query($query);

if ($result) {
    $row = $result->fetch_assoc();
    $total_candidates = $row['total_candidates'];
} else {
    $total_candidates = 0;
}

// Close the connection
$con->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Total Candidates</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            background-color: #f5f6fa;
        }

        .container {
            width: 300px;
            padding: 20px;
            background-color: #fff;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        .icon-circle {
            width: 60px;
            height: 60px;
            background-color: #EEF1FF;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
        }

        .icon-circle svg {
            width: 30px;
            height: 30px;
            fill: #4F67FF;
        }

        .title {
            color: #1a1a1a;
            font-size: 18px;
            margin-bottom: 10px;
            font-weight: 500;
        }

        .count {
            color: #4F67FF;
            font-size: 36px;
            font-weight: bold;
            margin: 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="icon-circle">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                <path d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14c-4.418 0-8 1.79-8 4v2h16v-2c0-2.21-3.582-4-8-4z"/>
            </svg>
        </div>
        <h2 class="title">Total Candidates</h2>
        <p class="count"><?php echo $total_candidates; ?></p>
    </div>
</body>
</html> 