<?php
// FILE: config/db.php

// Vercel uses standard environment variables
$servername = getenv('DB_HOST');
$username = getenv('DB_USER');
$password = getenv('DB_PASSWORD');
$dbname = getenv('DB_NAME');
$port = getenv('DB_PORT') ?: 3306; // Default to 3306 if not set

$conn = new mysqli($servername, $username, $password, $dbname, $port);

if ($conn->connect_error) {
    // Ideally, don't show specific errors to users in production
    die("Connection failed: " . $conn->connect_error);
}

date_default_timezone_set('Asia/Kolkata');
?>