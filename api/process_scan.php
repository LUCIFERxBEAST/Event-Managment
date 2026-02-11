<?php
session_start();
include '../config/db.php';

// 1. Validate Request
if (!isset($_GET['hash'])) {
    header("Location: dashboard.php");
    exit();
}

$qr_hash = $_GET['hash'];
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;

// 2. Fetch Ticket & Event Details
$sql = "SELECT r.*, h.title, h.venue, h.event_start, h.event_end, u.name as participant_name, u.email 
        FROM registrations r
        JOIN hackathons h ON r.hackathon_id = h.id
        JOIN users u ON r.user_id = u.id
        WHERE r.qr_code_hash = ?";

$stmt = $conn->prepare($sql);
$stmt->execute([$qr_hash]);
$ticket = $stmt->fetch();

if (!$ticket) {
    die("❌ Ticket not found.");
}

// 3. Security: Only the Owner or an Admin can view the full ticket
// (For now, we allow the owner. Guards will use a different scanner page.)
if ($ticket['user_id'] != $user_id) {
// Optional: You could redirect here, but for now we'll just show a warning
// die("⛔ You do not have permission to view this ticket.");
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <title>Ticket |
        <?php echo htmlspecialchars($ticket['title']); ?>
    </title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>

    <style>
        body {
            background-color: #1a1a2e;
            /* Dark Theme */
            color: white;
            font-family: 'Courier New', Courier, monospace;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .ticket-card {
            background: white;
            color: black;
            max-width: 400px;
            width: 100%;
            border-radius: 20px;
            overflow: hidden;
            position: relative;
            box-shadow: 0 0 30px rgba(0, 114, 255, 0.4);
        }

        .ticket-header {
            background: linear-gradient(45deg, #0d6efd, #0099ff);
            padding: 20px;
            text-align: center;
            color: white;
        }

        .ticket-body {
            padding: 30px;
            text-align: center;
        }

        .qr-holder {
            background: white;
            padding: 15px;
            border: 2px dashed #333;
            display: inline-block;
            margin: 20px 0;
        }

        .pin-box {
            background: #eee;
            padding: 10px;
            font-size: 24px;
            font-weight: bold;
            letter-spacing: 5px;
            cursor: pointer;
            border-radius: 8px;
            user-select: none;
        }

        .pin-box:hover {
            background: #ddd;
        }

        .hidden-pin span {
            filter: blur(5px);
        }

        /* Punch Hole Effect */
        .punch-hole {
            width: 40px;
            height: 40px;
            background: #1a1a2e;
            border-radius: 50%;
            position: absolute;
            top: 180px;
        }

        .punch-left {
            left: -20px;
        }

        .punch-right {
            right: -20px;
        }

        .status-badge {
            position: absolute;
            top: 15px;
            right: 15px;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .status-registered {
            background: white;
            color: #0d6efd;
        }

        .status-present {
            background: #198754;
            color: white;
            border: 2px solid white;
        }
    </style>
</head>

<body>

    <div class="ticket-card">
        <div class="ticket-header">
            <span
                class="status-badge <?php echo ($ticket['status'] == 'Present') ? 'status-present' : 'status-registered'; ?>">
                <?php echo $ticket['status']; ?>
            </span>
            <h5 class="mb-0 text-uppercase fw-bold">Official Entry Pass</h5>
            <small>Hackathon Hub ID</small>
        </div>

        <div class="punch-hole punch-left"></div>
        <div class="punch-hole punch-right"></div>

        <div class="ticket-body">
            <h4 class="fw-bold mb-1">
                <?php echo htmlspecialchars($ticket['title']); ?>
            </h4>
            <p class="text-muted small mb-4">📍
                <?php echo htmlspecialchars($ticket['venue']); ?>
            </p>

            <div class="qr-holder">
                <div id="qrcode"></div>
            </div>
            <p class="small text-muted">Scan this at the entrance</p>

            <hr class="my-4">

            <div class="row text-start">
                <div class="col-6">
                    <small class="text-muted">PARTICIPANT</small><br>
                    <strong>
                        <?php echo htmlspecialchars($ticket['participant_name']); ?>
                    </strong>
                </div>
                <div class="col-6 text-end">
                    <small class="text-muted">DATE</small><br>
                    <strong>
                        <?php echo date('M d', strtotime($ticket['event_start'])); ?>
                    </strong>
                </div>
            </div>

            <div class="mt-4">
                <small class="text-muted d-block mb-1">SECRET PIN (Tap to Show)</small>
                <div class="pin-box hidden-pin" onclick="this.classList.toggle('hidden-pin')">
                    <span>
                        <?php echo $ticket['access_pin']; ?>
                    </span>
                </div>
            </div>
        </div>

        <div class="p-3 bg-light text-center border-top">
            <a href="dashboard.php" class="text-decoration-none text-muted small">← Back to Dashboard</a>
        </div>
    </div>

    <script type="text/javascript">
        var qrData = "<?php echo $qr_hash; ?>"; // The unique hash
        new QRCode(document.getElementById("qrcode"), {
            text: qrData,
            width: 150,
            height: 150,
            colorDark: "#000000",
            colorLight: "#ffffff",
            correctLevel: QRCode.CorrectLevel.H
        });
    </script>

</body>

</html>