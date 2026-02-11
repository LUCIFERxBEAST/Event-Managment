<?php
session_start();
include 'config/db.php';

if (!isset($_GET['id'])) {
    header("Location: dashboard.php");
    exit();
}

$event_id = $_GET['id'];
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;

// 1. FETCH EVENT DETAILS
$stmt = $conn->prepare("SELECT * FROM hackathons WHERE id = :id");
$stmt->execute(['id' => $event_id]);
$event = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$event) {
    die("Event not found!");
}

// 2. CHECK IF USER ALREADY JOINED
$is_registered = false;
$ticket_hash = "";

if ($user_id > 0) {
    $stmt = $conn->prepare("SELECT qr_code_hash FROM registrations WHERE user_id = :user_id AND hackathon_id = :event_id");
    $stmt->execute(['user_id' => $user_id, 'event_id' => $event_id]);
    if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
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

    $stmt = $conn->prepare("INSERT INTO registrations (hackathon_id, user_id, qr_code_hash, access_pin) VALUES (:hackathon_id, :user_id, :qr_code_hash, :access_pin)");

    try {
        $stmt->execute([
            'hackathon_id' => $event_id,
            'user_id' => $user_id,
            'qr_code_hash' => $qr_hash,
            'access_pin' => $default_pin
        ]);

        // Success! Go straight to the ticket
        echo "<script>alert('✅ Successfully Registered!'); window.location.href = 'ticket.php?hash=$qr_hash';</script>";
        exit();
    }
    catch (PDOException $e) {
        $error = "Registration failed: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <title>
        <?php echo htmlspecialchars($event['title']); ?>
    </title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">

    <div class="container my-5">
        <a href="dashboard.php" class="btn btn-outline-secondary mb-3">← Back to Dashboard</a>

        <div class="card shadow-lg border-0 overflow-hidden">
            <div class="card-header bg-dark text-white p-4">
                <h1 class="fw-bold mb-0">
                    <?php echo htmlspecialchars($event['title']); ?>
                </h1>
                <p class="mb-0 text-white-50">📍
                    <?php echo htmlspecialchars($event['venue']); ?>
                </p>
            </div>

            <div class="card-body p-4">
                <div class="row mb-4">
                    <div class="col-md-6">
                        <h5 class="fw-bold text-primary">Start Time</h5>
                        <p class="fs-5">
                            <?php echo date('D, d M Y • h:i A', strtotime($event['event_start'])); ?>
                        </p>
                    </div>
                    <div class="col-md-6">
                        <h5 class="fw-bold text-danger">End Time</h5>
                        <p class="fs-5">
                            <?php echo date('D, d M Y • h:i A', strtotime($event['event_end'])); ?>
                        </p>
                    </div>
                </div>

                <hr>

                <div class="mb-5">
                    <h4 class="fw-bold">About the Event</h4>
                    <p style="white-space: pre-line;">
                        <?php echo htmlspecialchars($event['description']); ?>
                    </p>
                </div>

                <div class="mb-4">
                    <?php
$tags = explode(',', $event['event_tags']);
foreach ($tags as $tag) {
    if (trim($tag) != '')
        echo "<span class='badge bg-secondary me-1'>$tag</span>";
}
?>
                </div>

                <div class="d-grid gap-2">
                    <?php if ($user_id == 0): ?>
                    <a href="login.php" class="btn btn-warning btn-lg fw-bold">Login to Register</a>

                    <?php
elseif ($is_registered): ?>
                    <div class="alert alert-success text-center fw-bold">
                        ✅ You are registered!
                    </div>
                    <a href="ticket.php?hash=<?php echo $ticket_hash; ?>" class="btn btn-success btn-lg fw-bold">View My
                        Ticket</a>

                    <?php
else: ?>
                    <form method="POST">
                        <button type="submit" name="join_event" class="btn btn-primary btn-lg w-100 fw-bold py-3">
                            🚀 Confirm Registration
                        </button>
                    </form>
                    <?php
endif; ?>
                </div>

            </div>
        </div>
    </div>

</body>

</html>