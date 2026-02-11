<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include __DIR__ . '/../config/db.php';

echo "<h1>Starting Migration...</h1>";

try {
    // Force Add Column
    echo "<p>Attempting to add 'auth_token' column...</p>";
    $conn->exec("ALTER TABLE users ADD COLUMN auth_token VARCHAR(255) NULL");
    echo "<h2 style='color:green'>✅ SUCCESS: Column added.</h2>";
}
catch (PDOException $e) {
    echo "<p style='color:orange'>Notice: " . $e->getMessage() . " (Column might already exist)</p>";
}

try {
    // Force Add Index
    $conn->exec("CREATE INDEX idx_auth_token ON users(auth_token)");
    echo "<h2 style='color:green'>✅ SUCCESS: Index added.</h2>";
}
catch (PDOException $e) {
    echo "<p>Index notice: " . $e->getMessage() . "</p>";
}

echo "<h3>Migration Complete. <a href='login.php'>Go to Login</a></h3>";
?>