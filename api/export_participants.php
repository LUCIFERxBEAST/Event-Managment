<?php
// api/export_participants.php
session_start();
include __DIR__ . '/../config/db.php';

// 1. Security Check
if (!isset($_SESSION['user_id']) || !isset($_GET['event_id'])) {
    die("⛔ Access Denied.");
}

$user_id = $_SESSION['user_id'];
$event_id = intval($_GET['event_id']);

// 2. Verify Ownership
$stmt = $pdo->prepare("SELECT title FROM hackathons WHERE id = ? AND created_by = ?");
$stmt->execute([$event_id, $user_id]);
$check = $stmt->fetch();

if (!$check) {
    die("⛔ You do not own this event.");
}
$event_name = $check['title'];

// 3. Fetch Data (SAFE VERSION: NO DATES)
$sql = "SELECT u.name, u.email, r.status 
        FROM registrations r 
        JOIN users u ON r.user_id = u.id 
        WHERE r.hackathon_id = ?"; // Ordering by ID is safer if date is missing

$stmt_exp = $pdo->prepare($sql);
$stmt_exp->execute([$event_id]);


// 4. Download Headers
// This forces the browser to download a file instead of showing text
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $event_name . '_Participants.csv"');

// 5. Create File in Memory
$output = fopen('php://output', 'w');

// Add Column Headers (Removed 'Date' to match query)
fputcsv($output, ['Full Name', 'Email Address', 'Status']);

// Add Data Rows
while ($row = $stmt_exp->fetch()) {
    fputcsv($output, [
        $row['name'],
        $row['email'],
        $row['status']
    ]);
}

fclose($output);
exit();
?>