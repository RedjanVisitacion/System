<?php
session_start();
require_once 'connection.php';
require 'db.php';

$user_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT program_name FROM elecom_user_profile WHERE user_id = ?");
$stmt->execute([$user_id]);
$profile = $stmt->fetch(PDO::FETCH_ASSOC);

echo json_encode($profile);
?>
