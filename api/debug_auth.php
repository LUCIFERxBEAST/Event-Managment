<?php
session_start();
include __DIR__ . '/../config/db.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>

<head>
    <title>Auth Debug</title>
    <style>
        body {
            font-family: monospace;
            padding: 20px;
            background: #1a1a1a;
            color: #0f0;
        }

        .section {
            border: 1px solid #0f0;
            padding: 15px;
            margin: 10px 0;
        }

        .error {
            color: #f00;
        }

        .success {
            color: #0f0;
        }

        .warning {
            color: #ff0;
        }

        pre {
            background: #000;
            padding: 10px;
            overflow-x: auto;
        }
    </style>
</head>

<body>
    <h1>🔍 Authentication Debug Panel</h1>

    <div class="section">
        <h2>1. Session Status</h2>
        <pre><?php
echo "Session ID: " . session_id() . "\n";
echo "Session Data:\n";
print_r($_SESSION);
?></pre>
    </div>

    <div class="section">
        <h2>2. Cookies</h2>
        <pre><?php print_r($_COOKIE); ?></pre>
    </div>

    <div class="section">
        <h2>3. Database Check - auth_token column</h2>
        <?php
try {
    $stmt = $conn->query("SELECT column_name FROM information_schema.columns WHERE table_name='users' AND column_name='auth_token'");
    $exists = $stmt->fetch();

    if ($exists) {
        echo "<p class='success'>✅ auth_token column EXISTS in database</p>";
    }
    else {
        echo "<p class='error'>❌ auth_token column DOES NOT EXIST in database</p>";
        echo "<p class='warning'>⚠️ You need to run this SQL in Supabase:</p>";
        echo "<pre>ALTER TABLE users ADD COLUMN auth_token VARCHAR(255) NULL;</pre>";
    }
}
catch (Exception $e) {
    echo "<p class='error'>❌ Database error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
    </div>

    <div class="section">
        <h2>4. Cookie Token Validation</h2>
        <?php
if (isset($_COOKIE['auth_token'])) {
    echo "<p class='success'>✅ auth_token cookie is present</p>";
    echo "<p>Cookie value (first 20 chars): " . htmlspecialchars(substr($_COOKIE['auth_token'], 0, 20)) . "...</p>";

    $token_hash = hash('sha256', $_COOKIE['auth_token']);
    echo "<p>Token hash (first 20 chars): " . substr($token_hash, 0, 20) . "...</p>";

    try {
        $stmt = $conn->prepare("SELECT id, name, email, auth_token FROM users WHERE auth_token = :token");
        $stmt->execute(['token' => $token_hash]);
        $user = $stmt->fetch();

        if ($user) {
            echo "<p class='success'>✅ Token found in database!</p>";
            echo "<pre>User ID: {$user['id']}\nName: {$user['name']}\nEmail: {$user['email']}</pre>";
        }
        else {
            echo "<p class='error'>❌ Token NOT found in database</p>";
            echo "<p class='warning'>This means either:</p>";
            echo "<ul>";
            echo "<li>The token expired/was cleared</li>";
            echo "<li>You logged in before adding the auth_token column</li>";
            echo "</ul>";
            echo "<p><strong>Solution:</strong> Try logging in again</p>";
        }
    }
    catch (PDOException $e) {
        echo "<p class='error'>❌ Database query failed: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
}
else {
    echo "<p class='warning'>⚠️ No auth_token cookie found</p>";
}
?>
    </div>

    <div class="section">
        <h2>5. All Users (with auth_token status)</h2>
        <?php
try {
    $stmt = $conn->query("SELECT id, name, email, CASE WHEN auth_token IS NULL THEN 'NULL' ELSE 'SET' END as token_status FROM users ORDER BY id DESC LIMIT 5");
    $users = $stmt->fetchAll();

    echo "<table border='1' cellpadding='5' style='color: #0f0;'>";
    echo "<tr><th>ID</th><th>Name</th><th>Email</th><th>Token Status</th></tr>";
    foreach ($users as $u) {
        echo "<tr>";
        echo "<td>{$u['id']}</td>";
        echo "<td>{$u['name']}</td>";
        echo "<td>{$u['email']}</td>";
        echo "<td>{$u['token_status']}</td>";
        echo "</tr>";
    }
    echo "</table>";
}
catch (Exception $e) {
    echo "<p class='error'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
    </div>

    <div class="section">
        <h2>Actions</h2>
        <p><a href="login.php" style="color: #0ff;">← Back to Login</a></p>
        <p><a href="dashboard.php" style="color: #0ff;">→ Try Dashboard</a></p>
    </div>
</body>

</html>