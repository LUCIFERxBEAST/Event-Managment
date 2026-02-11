<?php
// api/scan_verify.php
header('Content-Type: application/json');
include __DIR__ . '/../config/db.php';

// 1. Get JSON Input
$input = json_decode(file_get_contents('php://input'), true);
$qr_hash = isset($input['qr_hash']) ? trim($input['qr_hash']) : '';
$event_id = isset($input['event_id']) ? intval($input['event_id']) : 0;

if (empty($qr_hash) || $event_id == 0) {
    echo json_encode(["status" => "error", "message" => "Invalid Scan Data (Missing Hash or Event ID)"]);
    exit();
}

// 2. Lookup Ticket
// We need to check if this QR code belongs to THIS event
$sql = "SELECT r.*, u.name, h.title 
        FROM registrations r
        JOIN users u ON r.user_id = u.id
        JOIN hackathons h ON r.hackathon_id = h.id
        WHERE r.qr_code_hash = :hash";

try {
    $stmt = $conn->prepare($sql);
    $stmt->execute(['hash' => $qr_hash]);
    $ticket = $stmt->fetch();

    if (!$ticket) {
        // TICKET DOES NOT EXIST
        echo json_encode([
            "status" => "error",
            "message" => "INVALID TICKET",
            "user" => "Unknown"
        ]);
        exit();
    }

    // 3. Verify Event Match
    if ($ticket['hackathon_id'] != $event_id) {
        echo json_encode([
            "status" => "error",
            "message" => "WRONG EVENT",
            "user" => "Ticket is for: " . $ticket['title']
        ]);
        exit();
    }

    // 4. Verify Status
    if ($ticket['status'] == 'Present') {
        echo json_encode([
            "status" => "warning",
            "message" => "ALREADY SCANNED",
            "user" => $ticket['name']
        ]);
    }
    else {
        // 5. Mark as Present
        $update = $conn->prepare("UPDATE registrations SET status = 'Present' WHERE id = :rid");
        $update->execute(['rid' => $ticket['id']]);

        echo json_encode([
            "status" => "success",
            "message" => "ACCESS GRANTED",
            "user" => $ticket['name']
        ]);
    }

}
catch (PDOException $e) {
    echo json_encode(["status" => "error", "message" => "System Error: " . $e->getMessage()]);
}
?>