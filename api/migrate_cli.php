<?php
// Load local environment variables for CLI
if (file_exists(__DIR__ . '/../config/env.php')) {
    include __DIR__ . '/../config/env.php';
}

// Manually load .env if env.php didn't work (CLI fallback)
if (!getenv('DB_HOST') && file_exists(__DIR__ . '/../.env')) {
    $lines = file(__DIR__ . '/../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0)
            continue;
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            putenv(trim($key) . '=' . trim($value));
        }
    }
}

include __DIR__ . '/../config/db.php';

echo "\n--- STARTING MIGRATION ---\n";

try {
    // 1. Check if column exists
    $stmt = $conn->query("SELECT column_name FROM information_schema.columns WHERE table_name='users' AND column_name='auth_token'");
    $exists = $stmt->fetch();

    if ($exists) {
        echo "✅ Column 'auth_token' ALREADY EXISTS.\n";
    }
    else {
        echo "⏳ Column MISSING. Attempting to add...\n";
        // 2. Add the column
        $sql = "ALTER TABLE users ADD COLUMN auth_token VARCHAR(255) NULL";
        $conn->exec($sql);
        echo "✅ SUCCESSFULLY ADDED 'auth_token' column.\n";

        // 3. Add Index
        try {
            $conn->exec("CREATE INDEX idx_auth_token ON users(auth_token)");
            echo "✅ Index created.\n";
        }
        catch (Exception $e) {
            echo "⚠️ Index creation failed (might exist): " . $e->getMessage() . "\n";
        }
    }

    // 4. Verify
    $stmt = $conn->query("SELECT column_name FROM information_schema.columns WHERE table_name='users' AND column_name='auth_token'");
    if ($stmt->fetch()) {
        echo "🎉 VERIFICATION PASSED: Column exists.\n";
    }
    else {
        echo "❌ VERIFICATION FAILED: Column still missing after attempt.\n";
    }

}
catch (PDOException $e) {
    echo "❌ FATAL ERROR: " . $e->getMessage() . "\n";
}
echo "--- END MIGRATION ---\n";
?>