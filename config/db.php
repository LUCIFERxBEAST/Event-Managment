<?php
/* FILE: config/db.php */


// Create Connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check Connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set Timezone
date_default_timezone_set('Asia/Kolkata');
?>