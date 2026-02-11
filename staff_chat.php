<?php
session_start();
include 'config/db.php';

// Security Check
if (!isset($_SESSION['staff_logged_in']) || $_SESSION['staff_role'] != 'Support') {
    die("⛔ Access Denied. Support Staff Only.");
}

$hackathon_id = $_SESSION['event_id'];

// Handle Reply
if (isset($_POST['reply'])) {
    $tid = $_POST['ticket_id'];
    $msg = $_POST['message'];
    $stmt = $pdo->prepare("INSERT INTO chat_messages (ticket_id, sender, message) VALUES (?, 'Staff', ?)");
    $stmt->execute([$tid, $msg]);
// Optionally set status back to resolved if needed, but usually we keep it open
}

// Fetch Active Tickets (Waiting for Human)
$stmt = $pdo->prepare("SELECT t.id, u.name, t.created_at FROM support_tickets t JOIN users u ON t.user_id = u.id WHERE t.hackathon_id = ? AND t.status = 'Waiting_For_Human'");
$stmt->execute([$hackathon_id]);
$tickets = $stmt->fetchAll();

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <title>Support Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <meta http-equiv="refresh" content="10">
</head>

<body class="bg-light">

    <nav class="navbar navbar-dark bg-danger shadow">
        <div class="container">
            <span class="navbar-brand fw-bold">🎧 Support Desk</span>
            <a href="index.php" class="btn btn-outline-light btn-sm">Exit</a>
        </div>
    </nav>

    <div class="container my-4">
        <h4 class="mb-4">🚨 Live Requests (Waitlist)</h4>

        <div class="row">
            <div class="row">
                <?php foreach ($tickets as $t): ?>
                <div class="col-md-6 mb-3">
                    <div class="card shadow-sm">
                        <div class="card-header bg-warning fw-bold d-flex justify-content-between">
                            <span>User:
                                <?php echo htmlspecialchars($t['name']); ?>
                            </span>
                            <small>Ticket #
                                <?php echo $t['id']; ?>
                            </small>
                        </div>
                        <div class="card-body"
                            style="height: 300px; overflow-y: auto; display: flex; flex-direction: column-reverse;">
                            <?php
    $stmt_msgs = $pdo->prepare("SELECT * FROM chat_messages WHERE ticket_id = ? ORDER BY timestamp DESC LIMIT 5");
    $stmt_msgs->execute([$t['id']]);
    $msgs = $stmt_msgs->fetchAll();
    foreach ($msgs as $m):
?>
                            <div
                                class="p-2 mb-2 rounded border <?php echo ($m['sender'] == 'Staff') ? 'bg-primary text-white text-end' : 'bg-light'; ?>">
                                <small class="fw-bold">
                                    <?php echo htmlspecialchars($m['sender']); ?>:
                                </small>
                                <?php echo htmlspecialchars($m['message']); ?>
                            </div>
                            <?php
    endforeach; ?>
                        </div>
                        <div class="card-footer">
                            <form method="POST" class="d-flex gap-2">
                                <input type="hidden" name="ticket_id" value="<?php echo $t['id']; ?>">
                                <input type="text" name="message" class="form-control" placeholder="Type reply..."
                                    required>
                                <button type="submit" name="reply" class="btn btn-success">Send</button>
                            </form>
                        </div>
                    </div>
                </div>
                <?php
endforeach; ?>

                <?php if (count($tickets) == 0): ?>
                <div class="col-12 text-center py-5 text-muted">
                    <h5>✅ All quiet! No pending requests.</h5>
                    <p>The AI bot is handling everything.</p>
                </div>
                <?php
endif; ?>
            </div>
        </div>

</body>

</html>