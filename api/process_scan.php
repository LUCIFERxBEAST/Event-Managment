<?php
// api/process_scan.php
header('Content-Type: application/json');
include __DIR__ . '/../config/db.php';

// 1. Get the JSON Data
$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['hash']) || !isset($data['event_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid Data']);
    exit();
}

$hash = $data['hash'];
$guard_event_id = intval($data['event_id']); // The event the guard is guarding

// 2. Search for the Ticket
// We explicitly check: Does this hash exist AND does it belong to this Event ID?
$sql = "SELECT r.id, u.name, r.status 
        FROM registrations r 
        JOIN users u ON r.user_id = u.id 
        WHERE r.qr_code_hash = ? 
        AND r.hackathon_id = ?"; // <--- STRICT CHECK

$stmt = $pdo->prepare($sql);
$stmt->execute([$hash, $guard_event_id]);
$row = $stmt->fetch();

if ($row) {
    // 3. Logic Checks
    if ($row['status'] == 'Present') {
        echo json_encode(['status' => 'error', 'message' => 'Already Inside!']);
    }
    else {
        // Mark them Present
        $pdo->prepare("UPDATE registrations SET status = 'Present' WHERE id = ?")->execute([$row['id']]);
        echo json_encode(['status' => 'success', 'name' => $row['name']]);
    }
}
else {
    // Ticket not found OR Ticket is for a different event
    echo json_encode(['status' => 'error', 'message' => 'Invalid Ticket for this Event']);
}
?>