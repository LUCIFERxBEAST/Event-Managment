<?php
session_start();
include '../config/db.php';

if (!isset($_GET['id'])) {
    header("Location: dashboard.php");
    exit();
}

$event_id = $_GET['id'];
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;

// 1. FETCH EVENT DETAILS
$stmt = $conn->prepare("SELECT * FROM hackathons WHERE id = :id");
$stmt->execute(['id' => $event_id]);
$event = $stmt->fetch();

if (!$event) {
    die("Event not found!");
}

// 2. CHECK IF USER ALREADY JOINED
$is_registered = false;
$ticket_hash = "";

if ($user_id > 0) {
    $check = $conn->query("SELECT qr_code_hash FROM registrations WHERE user_id = $user_id AND hackathon_id = $event_id");
    if ($row = $check->fetch()) {
        $is_registered = true;
        $ticket_hash = $row['qr_code_hash'];
    }
}

// 3. HANDLE NEW REGISTRATION
if (isset($_POST['join_event']) && $user_id > 0 && !$is_registered) {

    // Generate a Unique Ticket ID (The QR Code Data)
    // We mix the User ID, Event ID, and Randomness to make it un-guessable
    $qr_hash = md5($user_id . $event_id . time() . uniqid());

    // Create a simple PIN (e.g., 1234) or let them set it later. 
    // For now, let's set a default '0000' and ask them to update it on the ticket page.
    $default_pin = "0000";

    $stmt = $conn->prepare("INSERT INTO registrations (hackathon_id, user_id, qr_code_hash, access_pin) VALUES (:event_id, :user_id, :qr_hash, :pin)");

    try {
        if ($stmt->execute(['event_id' => $event_id, 'user_id' => $user_id, 'qr_hash' => $qr_hash, 'pin' => $default_pin])) {
            // Success! Go straight to the ticket
            echo "<script>alert('✅ Successfully Registered!'); window.location.href = 'ticket.php?hash=$qr_hash';</script>";
            exit();
        }
    }
    catch (PDOException $e) {
        $error = "Registration failed: " . $e->getMessage();
    }
}
?>

<?php
$page_title = $event['title'] . " | HackHub";
include '../includes/header.php';
?>

<div class="container my-5 fade-in">
    <a href="dashboard.php" class="btn btn-secondary mb-3" style="font-size:0.9rem;">← Back to Dashboard</a>

    <div class="glass-card" style="max-width: 900px; margin: 0 auto;">

        <div class="text-center mb-5">
            <h1 class="text-title text-gradient">
                <?php echo htmlspecialchars($event['title']); ?>
            </h1>
            <p class="text-subtitle">
                📍
                <?php echo htmlspecialchars($event['venue']); ?>
            </p>
        </div>

        <div class="grid-3 mb-5">
            <div class="glass-panel p-4 text-center">
                <h5 class="text-primary">Start Time</h5>
                <p class="fw-600 text-subtitle">
                    <?php echo date('D, d M Y • h:i A', strtotime($event['event_start'])); ?>
                </p>
            </div>
            <div class="glass-panel p-4 text-center">
                <h5 class="text-danger">End Time</h5>
                <p class="fw-600 text-subtitle">
                    <?php echo date('D, d M Y • h:i A', strtotime($event['event_end'])); ?>
                </p>
            </div>
            <div class="glass-panel p-4 text-center">
                <h5 style="color: var(--accent-color);">Tags</h5>
                <div>
                    <?php
$tags = explode(',', $event['event_tags']);
foreach ($tags as $tag) {
    if (trim($tag) != '')
        echo "<span class='badge bg-secondary' style='margin: 2px;'>$tag</span>";
}
?>
                </div>
            </div>
        </div>

        <div class="mb-5">
            <h3 style="border-bottom: 2px solid #eee; padding-bottom: 10px;">About the Event</h3>
            <p style="white-space: pre-line; color: #444; font-size: 1.05rem; line-height: 1.8;">
                <?php echo htmlspecialchars($event['description']); ?>
            </p>
        </div>

        <div class="text-center">
            <?php if ($user_id == 0): ?>
            <a href="login.php" class="btn btn-primary" style="font-size: 1.2rem; padding: 1rem 3rem;">
                Login to Register
            </a>

            <?php
elseif ($is_registered): ?>
            <div class="alert alert-success" style="display: inline-block;">
                ✅ You are registered!
            </div>
            <br><br>
            <a href="ticket.php?hash=<?php echo $ticket_hash; ?>" class="btn btn-success">
                View My Ticket
            </a>

            <?php
else: ?>
            <form method="POST">
                <button type="submit" name="join_event" class="btn btn-primary"
                    style="font-size: 1.2rem; padding: 1rem 3rem; width: 100%;">
                    🚀 Confirm Registration
                </button>
            </form>
            <?php
endif; ?>
        </div>

    </div>
</div>

<?php include '../includes/footer.php'; ?>