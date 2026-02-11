<?php
// api/chat_processor.php
session_start();
include '../config/db.php';
header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';

// 1. USER SENDS MESSAGE
if ($action == 'send_message') {
    $ticket_id = $input['ticket_id'];
    $sender = $input['sender']; // 'User' or 'Staff'
    $msg = $input['message'];

    // Save Message
    $stmt = $pdo->prepare("INSERT INTO chat_messages (ticket_id, sender, message) VALUES (?, ?, ?)");
    $stmt->execute([$ticket_id, $sender, $msg]);

    // IF USER SENT IT -> TRIGGER BOT RESPONSE
    if ($sender == 'User') {
        // Check if ticket is in 'Bot_Active' mode
        $stmt_check = $pdo->prepare("SELECT status FROM support_tickets WHERE id=?");
        $stmt_check->execute([$ticket_id]);
        $status = $stmt_check->fetch()['status'];

        if ($status == 'Bot_Active') {
            $bot_reply = getBotReply($msg);

            // If Bot has an answer, send it
            if ($bot_reply) {
                sleep(1); // Fake "typing" delay
                $stmt->execute([$ticket_id, 'Bot', $bot_reply]);
            }
            else {
                // Bot doesn't know -> Escalate?
                // For now, we just say "I don't know, click 'Ask Human' button."
                $stmt->execute([$ticket_id, 'Bot', 'I am not sure about that. Please click the "Talk to Human" button to connect with staff.']);
            }
        }
    }
    echo json_encode(["status" => "success"]);
}

// 2. CREATE / GET TICKET
// 2. CREATE / GET TICKET
if ($action == 'init_chat') {
    $user_id = $_SESSION['user_id'];
    $hackathon_id = $input['hackathon_id'];

    // Check if open ticket exists
    $stmt = $pdo->prepare("SELECT id, status FROM support_tickets WHERE user_id=? AND hackathon_id=? AND status != 'Resolved'");
    $stmt->execute([$user_id, $hackathon_id]);

    if ($row = $stmt->fetch()) {
        $ticket_id = $row['id'];
        $status = $row['status'];
    }
    else {
        // Create new ticket
        $stmt_ins = $pdo->prepare("INSERT INTO support_tickets (hackathon_id, user_id, status) VALUES (?, ?, 'Bot_Active')");
        $stmt_ins->execute([$hackathon_id, $user_id]);
        $ticket_id = $pdo->lastInsertId();
        $status = 'Bot_Active';

        // Bot Greeting
        $stmt_msg = $pdo->prepare("INSERT INTO chat_messages (ticket_id, sender, message) VALUES (?, 'Bot', 'Hello! I am the Event AI. Ask me about WiFi, Food, or Schedule!')");
        $stmt_msg->execute([$ticket_id]);
    }

    echo json_encode(["ticket_id" => $ticket_id, "status" => $status]);
}

// 3. FETCH MESSAGES (Polling)
if ($action == 'get_messages') {
    $ticket_id = $input['ticket_id'];
    $msgs = [];
    $stmt = $pdo->prepare("SELECT * FROM chat_messages WHERE ticket_id=? ORDER BY timestamp ASC");
    $stmt->execute([$ticket_id]);
    while ($row = $stmt->fetch()) {
        $msgs[] = $row;
    }
    echo json_encode(["messages" => $msgs]);
}

// 4. ESCALATE TO HUMAN
if ($action == 'escalate') {
    $ticket_id = $input['ticket_id'];
    $pdo->prepare("UPDATE support_tickets SET status='Waiting_For_Human' WHERE id=?")->execute([$ticket_id]);
    $pdo->prepare("INSERT INTO chat_messages (ticket_id, sender, message) VALUES (?, 'Bot', '✅ I have notified the support staff. A human will join shortly.')")->execute([$ticket_id]);
    echo json_encode(["status" => "escalated"]);
}

// --- SIMULATED AI LOGIC ---
function getBotReply($text)
{
    $text = strtolower($text);
    if (strpos($text, 'wifi') !== false || strpos($text, 'internet') !== false)
        return "The WiFi SSID is: **HackHub_Guest** and Password is: **code2026**";
    if (strpos($text, 'food') !== false || strpos($text, 'lunch') !== false)
        return "Lunch is served at 1:00 PM in the Cafeteria. Dinner is at 8:00 PM.";
    if (strpos($text, 'toilet') !== false || strpos($text, 'washroom') !== false)
        return "Restrooms are located near the elevator on every floor.";
    if (strpos($text, 'submit') !== false || strpos($text, 'deadline') !== false)
        return "Submission deadline is strictly 10:00 AM tomorrow. Upload on dashboard.";
    return null; // No keyword match
}
?>