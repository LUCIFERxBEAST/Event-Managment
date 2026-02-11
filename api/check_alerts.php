<?php
// api/check_alerts.php
include '../config/db.php';
header('Content-Type: application/json');

// Get the list of hackathon IDs passed from the dashboard
// The frontend will send: ?ids=1,2,3 (Events the user has joined)
$ids = isset($_GET['ids']) ? $_GET['ids'] : '';

if (empty($ids)) {
    echo json_encode(["status" => "none"]);
    exit();
}

// Clean the input to prevent SQL Injection
$ids_array = array_map('intval', explode(',', $ids));
$safe_ids = implode(',', $ids_array);

// Check for ANY active alerts in these events
$sql = "SELECT title, alert_message FROM hackathons WHERE id IN ($safe_ids) AND alert_message IS NOT NULL AND alert_message != ''";
$result = $conn->query($sql);

if ($row = $result->fetch_assoc()) {
    echo json_encode([
        "status" => "alert",
        "event" => $row['title'],
        "message" => $row['alert_message']
    ]);
} else {
    echo json_encode(["status" => "none"]);
}
?>