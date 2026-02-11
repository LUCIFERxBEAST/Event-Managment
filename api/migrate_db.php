<?php
include __DIR__ . '/../config/db.php';

echo "<h2>Database Migration Tool</h2>";

try {
    // 1. Check if column exists
    $check = $conn->query("SELECT column_name FROM information_schema.columns WHERE table_name='users' AND column_name='auth_token'");

    if ($check->rowCount() > 0) {
        echo "<div style='color: green; border: 1px solid green; padding: 10px;'>✅ Column 'auth_token' already exists. No action needed.</div>";
    }
    else {
        // 2. Add the column
        $sql = "ALTER TABLE users ADD COLUMN auth_token VARCHAR(255) NULL";
        $conn->exec($sql);
        echo "<div style='color: green; border: 1px solid green; padding: 10px;'>✅ Successfully added 'auth_token' column to 'users' table.</div>";

        // 3. Add Index (Optional but good for performance)
        $conn->exec("CREATE INDEX idx_auth_token ON users(auth_token)");
        echo "<div>Created index on auth_token.</div>";
    }

}
catch (PDOException $e) {
    echo "<div style='color: red; border: 1px solid red; padding: 10px;'>❌ Error: " . $e->getMessage() . "</div>";
}
?>