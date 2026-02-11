<?php
include 'config/db.php';

try {
    echo "Testing Database Connection...\n";
    $stmt = $pdo->query("SELECT count(*) FROM users");
    echo "✅ Connection Successful! Users table exists.\n";
    $count = $stmt->fetchColumn();
    echo "Users count: $count\n";
}
catch (PDOException $e) {
    echo "❌ Database Error: " . $e->getMessage() . "\n";
    if (strpos($e->getMessage(), 'relation "users" does not exist') !== false) {
        echo "⚠️ Schema likely missing. You need to run the setup script.\n";
    }
}
?>