<?php
session_start();
include '../config/db.php';

// 1. Security Check
if (!isset($_SESSION['user_id']) || !isset($_GET['id'])) {
    header("Location: dashboard.php");
    exit();
}

$event_id = $_GET['id'];
$user_id = $_SESSION['user_id'];

// 2. Fetch Event Data (Ensure YOU are the owner)
$stmt = $conn->prepare("SELECT * FROM hackathons WHERE id = :id AND created_by = :user_id");
$stmt->execute(['id' => $event_id, 'user_id' => $user_id]);
$event = $stmt->fetch();

if (!$event)
    die("⛔ Access Denied. You are not the organizer of this event.");

// --- 📢 NEW: HANDLE EMERGENCY BROADCAST ---
if (isset($_POST['broadcast_alert'])) {
    $msg = $_POST['alert_msg'];
    $stmt = $conn->prepare("UPDATE hackathons SET alert_message = :msg WHERE id = :id");
    $stmt->execute(['msg' => $msg, 'id' => $event_id]);
    // Refresh to show updated status
    header("Location: manage_event.php?id=$event_id&msg=sent");
    exit();
}
if (isset($_POST['clear_alert'])) {
    $stmt = $conn->prepare("UPDATE hackathons SET alert_message = NULL WHERE id = :id");
    $stmt->execute(['id' => $event_id]);
    header("Location: manage_event.php?id=$event_id&msg=cleared");
    exit();
}
// ------------------------------------------

// 3. Handle "Add Staff" Action
if (isset($_POST['add_staff'])) {
    $role = $_POST['role'];
    $staff_name = $_POST['name'];

    // Generate Random Token (e.g., G-8291)
    $prefix = ($role == 'Guard') ? 'G-' : 'S-';
    $token = $prefix . rand(1000, 9999);

    $ins = $conn->prepare("INSERT INTO event_staff (hackathon_id, name, role, access_token) VALUES (:eid, :name, :role, :token)");
    $ins->execute([
        'eid' => $event_id,
        'name' => $staff_name,
        'role' => $role,
        'token' => $token
    ]);
}

// 4. Handle "Delete Staff"
if (isset($_GET['delete_staff'])) {
    $staff_id = $_GET['delete_staff'];
    $del = $conn->prepare("DELETE FROM event_staff WHERE id=:sid AND hackathon_id=:eid");
    $del->execute(['sid' => $staff_id, 'eid' => $event_id]);
    header("Location: manage_event.php?id=$event_id");
    exit();
}

// 5. Fetch Current Staff
$staff_list = $conn->prepare("SELECT * FROM event_staff WHERE hackathon_id = :eid ORDER BY role");
$staff_list->execute(['eid' => $event_id]);

$page_title = "Manage: " . $event['title'] . " | HackHub";
include '../includes/header.php';
?>

<div class="container my-5 fade-in">

    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
        <h2 style="margin: 0;">⚙️ Command Center:
            <?php echo htmlspecialchars($event['title']); ?>
        </h2>
        <a href="dashboard.php" class="btn btn-secondary">← Back</a>
    </div>

    <?php if (isset($_GET['msg']) && $_GET['msg'] == 'sent'): ?>
    <div class="alert alert-danger" style="text-align: center;">🚨 ALERT BROADCASTED SUCCESSFULLY!</div>
    <?php
elseif (isset($_GET['msg']) && $_GET['msg'] == 'cleared'): ?>
    <div class="alert alert-success" style="text-align: center;">✅ Alert Cleared. System Normal.</div>
    <?php
endif; ?>

    <div class="grid-3" style="grid-template-columns: 1fr 1fr; align-items: start;">

        <!-- Left Column -->
        <div class="glass-card">
            <h4 class="mb-3">👥 Assign Staff</h4>

            <form method="POST" style="display: flex; gap: 10px; margin-bottom: 2rem;">
                <input type="text" name="name" class="form-control" placeholder="Name (e.g. Rahul)" required
                    style="flex: 2;">
                <select name="role" class="form-control" style="flex: 1;">
                    <option value="Guard">👮 Guard</option>
                    <option value="Support">🎧 Support</option>
                </select>
                <button type="submit" name="add_staff" class="btn btn-primary">+ Add</button>
            </form>

            <h6 style="color: #666; margin-bottom: 1rem;">Active Tokens</h6>
            <div style="max-height: 400px; overflow-y: auto;">
                <?php while ($s = $staff_list->fetch()): ?>
                <div class="list-group-item">
                    <div>
                        <strong>
                            <?php echo $s['role']; ?>:
                        </strong>
                        <?php echo $s['name']; ?>
                        <br>
                        <span class="badge bg-secondary" style="margin-top: 5px; letter-spacing: 1px;">
                            <?php echo $s['access_token']; ?>
                        </span>
                    </div>
                    <a href="?id=<?php echo $event_id; ?>&delete_staff=<?php echo $s['id']; ?>" class="btn btn-danger"
                        style="padding: 0.3rem 0.8rem; font-size: 0.8rem;">Revoke</a>
                </div>
                <?php
endwhile; ?>
                <?php if ($staff_list->rowCount() == 0)
    echo "<small class='text-muted'>No staff assigned yet.</small>"; ?>
            </div>
        </div>

        <!-- Right Column -->
        <div style="display: flex; flex-direction: column; gap: 2rem;">

            <div class="glass-card">
                <h4 class="mb-3">🔗 Quick Links</h4>
                <div style="display: grid; gap: 1rem;">
                    <a href="guard_login.php" target="_blank" class="btn btn-secondary" style="text-align: left;">
                        🛡️ <strong>Guard Login Page</strong> (Share this URL)
                    </a>
                    <a href="event_details.php?id=<?php echo $event_id; ?>" target="_blank" class="btn btn-primary"
                        style="text-align: left;">
                        🌍 <strong>Public Event Page</strong>
                    </a>
                </div>
            </div>

            <div class="glass-card" style="border: 2px solid #ff4757; background: rgba(255, 71, 87, 0.05);">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                    <h4 style="color: #ff4757; margin: 0;">📢 Emergency Alert</h4>
                    <span class="badge bg-danger">ADMIN ONLY</span>
                </div>
                <p class="small text-muted" style="margin-bottom: 1rem;">Send a popup notification to all
                    participant screens instantly.</p>

                <?php if (!empty($event['alert_message'])): ?>
                <div class="alert alert-danger" style="text-align: center; margin-bottom: 1rem;">
                    <strong>⚠️ ACTIVE NOW:</strong>
                    <?php echo htmlspecialchars($event['alert_message']); ?>
                </div>
                <?php
endif; ?>

                <form method="POST">
                    <div class="form-group">
                        <textarea name="alert_msg" class="form-control" rows="2"
                            placeholder="e.g. Lunch is served! / Fire Drill!" style="border-color: #ff4757;"
                            required></textarea>
                    </div>
                    <div style="display: flex; gap: 10px;">
                        <button type="submit" name="broadcast_alert" class="btn btn-danger" style="flex: 1;">🔴
                            BROADCAST</button>
                        <button type="submit" name="clear_alert" class="btn btn-secondary">Clear</button>
                    </div>
                </form>
            </div>

            <div class="glass-card">
                <h4 class="mb-3">🤖 AI Help Desk</h4>
                <div class="alert alert-success">
                    <p class="mb-0">
                        <strong>Status:</strong> AI Active<br>
                        Participants can ask questions. If the AI gets stuck, it will route tickets to your
                        <strong>Support Staff</strong>.
                    </p>
                </div>
                <button class="btn btn-secondary" style="width: 100%; opacity: 0.6; cursor: not-allowed;"
                    disabled>Auto-Managed by AI</button>
            </div>

        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>