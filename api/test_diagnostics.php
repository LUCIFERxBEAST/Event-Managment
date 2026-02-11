<?php
include __DIR__ . '/../config/db.php';

echo "<h2>1. Table Schema: users</h2>";
try {
    $stmt = $conn->query("SELECT column_name, data_type FROM information_schema.columns WHERE table_name = 'users'");
    $columns = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    echo "<pre>";
    print_r($columns);
    echo "</pre>";
}
catch (Exception $e) {
    echo "Error fetching schema: " . $e->getMessage();
}

echo "<h2>2. Test OTP Update</h2>";
// Try to update a dummy user (or just check if the query runs without error)
// We won't actually change anything unless we have a valid ID, but we can prepare the statement.
try {
    $otp = "123456";
    $expiry = date("Y-m-d H:i:s");
    $id = 0; // Invalid ID

    $update = $conn->prepare("UPDATE users SET otp_code=:otp, otp_expiry=:expiry, otp_failed_attempts=0 WHERE id=:id");
    $update->execute(['otp' => $otp, 'expiry' => $expiry, 'id' => $id]);
    echo "✅ OTP Update Query executed successfully (RowCount: " . $update->rowCount() . ")";
}
catch (Exception $e) {
    echo "❌ OTP Update Failed: " . $e->getMessage();
}
?>