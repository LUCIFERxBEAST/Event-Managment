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
$stmt->bind_param("s", $qr_hash);
$stmt->execute();
$ticket = $stmt->get_result()->fetch_assoc();

if (!$ticket) {
    die("❌ Ticket not found.");
}

// 3. Security: Only the Owner or an Admin can view the full ticket
// (For now, we allow the owner. Guards will use a different scanner page.)
if ($ticket['user_id'] != $user_id) {
// Optional: You could redirect here, but for now we'll just show a warning
// die("⛔ You do not have permission to view this ticket.");
}

$page_title = "Ticket | " . $ticket['title'];
include '../includes/header.php';
?>

<div class="ticket-wrapper fade-in">
    <div class="ticket-card">
        <div class="ticket-header">
            <span class="status-badge">
                <?php echo $ticket['status']; ?>
            </span>
            <h5 style="margin: 0; font-weight: 800; letter-spacing: 1px;">OFFICIAL ENTRY</h5>
            <small style="opacity: 0.8;">HackHub ID</small>
        </div>

        <div class="ticket-body">
            <h4 style="font-weight: 700; margin-bottom: 5px; color: var(--primary-color);">
                <?php echo htmlspecialchars($ticket['title']); ?>
            </h4>
            <p style="color: #666; margin-bottom: 1.5rem; font-size: 0.9rem;">📍
                <?php echo htmlspecialchars($ticket['venue']); ?>
            </p>

            <div class="qr-holder">
                <div id="qrcode"></div>
            </div>
            <p style="font-size: 0.85rem; color: #999;">Scan at entrance</p>

            <div style="border-top: 2px dashed #eee; margin: 2rem 0;"></div>

            <div style="display: flex; justify-content: space-between; text-align: left;">
                <div style="width: 48%;">
                    <small style="color: #999;font-size:0.7rem; font-weight:700;">PARTICIPANT</small><br>
                    <strong style="font-size: 0.95rem;">
                        <?php echo htmlspecialchars($ticket['participant_name']); ?>
                    </strong>
                </div>
                <div style="width: 48%; text-align: right;">
                    <small style="color: #999;font-size:0.7rem; font-weight:700;">DATE</small><br>
                    <strong style="font-size: 0.95rem;">
                        <?php echo date('M d', strtotime($ticket['event_start'])); ?>
                    </strong>
                </div>
            </div>

            <div style="margin-top: 2rem;">
                <small
                    style="color: #999; display: block; margin-bottom: 5px; font-size: 0.7rem; font-weight:700;">SECRET
                    PIN (TAP TO REVEAL)</small>
                <div class="pin-box hidden-pin" onclick="this.classList.toggle('hidden-pin')">
                    <span style="color: var(--secondary-color);">
                        <?php echo $ticket['access_pin']; ?>
                    </span>
                </div>
            </div>
        </div>

        <div style="padding: 15px; background: #f8f9fa; text-align: center; border-top: 1px solid #eee;">
            <a href="dashboard.php" style="font-size: 0.85rem; font-weight: 600; color: #999;">← Back to
                Dashboard</a>
        </div>
    </div>
</div>

<script type="text/javascript">
    var qrData = "<?php echo $qr_hash; ?>"; // The unique hash
    // Clear previous if any (though logic is fresh)
    document.getElementById("qrcode").innerHTML = "";
    new QRCode(document.getElementById("qrcode"), {
        text: qrData,
        width: 150,
        height: 150,
        colorDark: "#2F2E41",
        colorLight: "#ffffff",
        correctLevel: QRCode.CorrectLevel.H
    });
</script>

<?php include '../includes/footer.php'; ?>