<?php
/* FILE: config/db.php */

$servername = "sql202.infinityfree.com";
$username   = "if0_41084474";      // Default for XAMPP/WAMP
$password   = "lLJ2n2psmRUz7V";          // Default is empty
$dbname     = "if0_41084474_hackathon"; // Ensure this matches your phpMyAdmin DB name

// Create Connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check Connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set Timezone
date_default_timezone_set('Asia/Kolkata'); 
?>