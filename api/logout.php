<?php
session_start();
include __DIR__ . '/../config/db.php';

// 0. Clear Persistent Auth (DB & Cookie)
if (isset($_SESSION['user_id'])) {
    $uid = $_SESSION['user_id'];
    $conn->query("UPDATE users SET auth_token = NULL WHERE id = $uid");
}

if (isset($_COOKIE['auth_token'])) {
    setcookie('auth_token', '', time() - 3600, '/');
}

// 1. Unset all session variables
$_SESSION = array();

// 2. Destroy the session cookie (if it exists)
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 3. Destroy the session
session_destroy();

// 4. Redirect to Home Page
header("Location: index.php");
exit();
?>