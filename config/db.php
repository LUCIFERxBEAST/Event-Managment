<?php
/* FILE: config/db.php - PostgreSQL/Supabase Connection using PDO */

// Load environment variables
require_once __DIR__ . '/env.php';

// Get database configuration from environment variables
$db_host = getenv('DB_HOST');
$db_port = getenv('DB_PORT') ?: '5432';
$db_name = getenv('DB_NAME') ?: 'postgres';
$db_user = getenv('DB_USER') ?: 'postgres';
$db_password = getenv('DB_PASSWORD');

// Validate required credentials
if (empty($db_host) || empty($db_password)) {
    die("Error: Database credentials not configured. Please update your .env file with DB_HOST and DB_PASSWORD");
}

// Create PostgreSQL PDO connection
try {
    $dsn = "pgsql:host=$db_host;port=$db_port;dbname=$db_name";
    $conn = new PDO($dsn, $db_user, $db_password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    // Set timezone
    $timezone = getenv('DB_TIMEZONE') ?: 'Asia/Kolkata';
    date_default_timezone_set($timezone);

}
catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>