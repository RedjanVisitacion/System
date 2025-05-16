<?php
// Define database credentials
define("DB_USER", 'root');
define("DB_PASSWORD", 'Dilikom@gsaba2025');
define("DB_NAME", 'elecom');
define("DB_HOST", '114.29.238.76');

// Create a database connection
$con = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
if ($con->connect_error) {
    die("Connection failed: " . $con->connect_error);
}
?>
