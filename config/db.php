<?php
// config/db.php
include_once __DIR__ . '/load_env.php';

$host = getenv('DB_HOST');
$db = getenv('DB_NAME');
$user = getenv('DB_USER');
$pass = getenv('DB_PASSWORD');
$port = getenv('DB_PORT') ?: "5432";

if (!$host || !$db) {
// Fallback or Error
// error_log("Database configuration missing in .env");
}

$dsn = "pgsql:host=$host;port=$port;dbname=$db;";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
}
catch (\PDOException $e) {
    // For security, don't echo full error in production, but for dev migration it's helpful
    die("Database Connection Failed: " . $e->getMessage());
}
?>