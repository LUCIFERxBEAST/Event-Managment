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
    $msg = $conn->real_escape_string($input['message']);

    // Save Message
    $conn->query("INSERT INTO chat_messages (ticket_id, sender, message) VALUES ($ticket_id, '$sender', '$msg')");
    
    // IF USER SENT IT -> TRIGGER BOT RESPONSE
    if ($sender == 'User') {
        // Check if ticket is in 'Bot_Active' mode
        $check = $conn->query("SELECT status FROM support_tickets WHERE id=$ticket_id");
        $status = $check->fetch_assoc()['status'];

        if ($status == 'Bot_Active') {
            $bot_reply = getBotReply($msg);
            
            // If Bot has an answer, send it
            if ($bot_reply) {
                sleep(1); // Fake "typing" delay
                $conn->query("INSERT INTO chat_messages (ticket_id, sender, message) VALUES ($ticket_id, 'Bot', '$bot_reply')");
            } else {
                // Bot doesn't know -> Escalate?
                // For now, we just say "I don't know, click 'Ask Human' button."
                $conn->query("INSERT INTO chat_messages (ticket_id, sender, message) VALUES ($ticket_id, 'Bot', 'I am not sure about that. Please click the \"Talk to Human\" button to connect with staff.')");
            }
        }
    }
    echo json_encode(["status" => "success"]);
}

// 2. CREATE / GET TICKET
if ($action == 'init_chat') {
    $user_id = $_SESSION['user_id'];
    $hackathon_id = $input['hackathon_id'];

    // Check if open ticket exists
    $q = $conn->query("SELECT id, status FROM support_tickets WHERE user_id=$user_id AND hackathon_id=$hackathon_id AND status != 'Resolved'");
    
    if ($row = $q->fetch_assoc()) {
        $ticket_id = $row['id'];
        $status = $row['status'];
    } else {
        // Create new ticket
        $conn->query("INSERT INTO support_tickets (hackathon_id, user_id, status) VALUES ($hackathon_id, $user_id, 'Bot_Active')");
        $ticket_id = $conn->insert_id;
        $status = 'Bot_Active';
        
        // Bot Greeting
        $conn->query("INSERT INTO chat_messages (ticket_id, sender, message) VALUES ($ticket_id, 'Bot', 'Hello! I am the Event AI. Ask me about WiFi, Food, or Schedule!')");
    }
    
    echo json_encode(["ticket_id" => $ticket_id, "status" => $status]);
}

// 3. FETCH MESSAGES (Polling)
if ($action == 'get_messages') {
    $ticket_id = $input['ticket_id'];
    $msgs = [];
    $q = $conn->query("SELECT * FROM chat_messages WHERE ticket_id=$ticket_id ORDER BY timestamp ASC");
    while ($row = $q->fetch_assoc()) {
        $msgs[] = $row;
    }
    echo json_encode(["messages" => $msgs]);
}

// 4. ESCALATE TO HUMAN
if ($action == 'escalate') {
    $ticket_id = $input['ticket_id'];
    $conn->query("UPDATE support_tickets SET status='Waiting_For_Human' WHERE id=$ticket_id");
    $conn->query("INSERT INTO chat_messages (ticket_id, sender, message) VALUES ($ticket_id, 'Bot', '✅ I have notified the support staff. A human will join shortly.')");
    echo json_encode(["status" => "escalated"]);
}

// --- SIMULATED AI LOGIC ---
function getBotReply($text) {
    $text = strtolower($text);
    if (strpos($text, 'wifi') !== false || strpos($text, 'internet') !== false) return "The WiFi SSID is: **HackHub_Guest** and Password is: **code2026**";
    if (strpos($text, 'food') !== false || strpos($text, 'lunch') !== false) return "Lunch is served at 1:00 PM in the Cafeteria. Dinner is at 8:00 PM.";
    if (strpos($text, 'toilet') !== false || strpos($text, 'washroom') !== false) return "Restrooms are located near the elevator on every floor.";
    if (strpos($text, 'submit') !== false || strpos($text, 'deadline') !== false) return "Submission deadline is strictly 10:00 AM tomorrow. Upload on dashboard.";
    return null; // No keyword match
}
?>