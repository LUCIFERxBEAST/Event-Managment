<?php
// api/export_participants.php
session_start();
include '../config/db.php';

// 1. Security Check
if (!isset($_SESSION['user_id']) || !isset($_GET['event_id'])) {
    die("⛔ Access Denied.");
}

$user_id = $_SESSION['user_id'];
$event_id = intval($_GET['event_id']);

// 2. Verify Ownership
$check = $conn->query("SELECT title FROM hackathons WHERE id = $event_id AND created_by = $user_id");
if (!$check || $check->num_rows == 0) {
    die("⛔ You do not own this event.");
}
$event_name = $check->fetch_assoc()['title'];

// 3. Fetch Data (SAFE VERSION: NO DATES)
$sql = "SELECT u.name, u.email, r.status 
        FROM registrations r 
        JOIN users u ON r.user_id = u.id 
        WHERE r.hackathon_id = $event_id"; // Ordering by ID is safer if date is missing

$result = $conn->query($sql);

if (!$result) {
    die("❌ Export Error: " . $conn->error);
}

// 4. Download Headers
// This forces the browser to download a file instead of showing text
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $event_name . '_Participants.csv"');

// 5. Create File in Memory
$output = fopen('php://output', 'w');

// Add Column Headers (Removed 'Date' to match query)
fputcsv($output, ['Full Name', 'Email Address', 'Status']);

// Add Data Rows
while ($row = $result->fetch_assoc()) {
    fputcsv($output, [
        $row['name'], 
        $row['email'], 
        $row['status']
    ]);
}

fclose($output);
exit();
?>