<?php
session_start();
include __DIR__ . '/../config/db.php';

// Security
if (!isset($_SESSION['user_id']) || !isset($_GET['id'])) {
    die("⛔ Access Denied");
}

$event_id = intval($_GET['id']);
$user_id = $_SESSION['user_id'];

// Check Ownership
$stmt = $pdo->prepare("SELECT * FROM hackathons WHERE id = ? AND created_by = ?");
$stmt->execute([$event_id, $user_id]);
$event = $stmt->fetch();

if (!$event) {
    die("⛔ Access Denied");
}

// --- ACTIONS ---
if (isset($_POST['broadcast_alert'])) {
    $msg = $_POST['alert_msg'];
    $stmt = $pdo->prepare("UPDATE hackathons SET alert_message = ? WHERE id = ?");
    $stmt->execute([$msg, $event_id]);
    header("Location: manage_event.php?id=$event_id&msg=sent");
    exit();
}
if (isset($_POST['clear_alert'])) {
    $pdo->query("UPDATE hackathons SET alert_message = NULL WHERE id = $event_id");
    header("Location: manage_event.php?id=$event_id&msg=cleared");
    exit();
}
if (isset($_POST['add_staff'])) {
    $role = $_POST['role'];
    $staff_name = $_POST['name'];
    $token = ($role == 'Guard' ? 'G-' : 'S-') . rand(1000, 9999);
    $stmt = $pdo->prepare("INSERT INTO event_staff (hackathon_id, name, role, access_token) VALUES (?, ?, ?, ?)");
    $stmt->execute([$event_id, $staff_name, $role, $token]);
}
if (isset($_GET['delete_staff'])) {
    $sid = intval($_GET['delete_staff']);
    $stmt = $pdo->prepare("DELETE FROM event_staff WHERE id=? AND hackathon_id=?");
    $stmt->execute([$sid, $event_id]);
    header("Location: manage_event.php?id=$event_id");
    exit();
}

$stmt_staff = $pdo->prepare("SELECT * FROM event_staff WHERE hackathon_id = ? ORDER BY role");
$stmt_staff->execute([$event_id]);
$staff_list = $stmt_staff->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <title>Manage:
        <?php echo htmlspecialchars($event['title']); ?>
    </title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light pb-5">

    <div class="container my-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>⚙️ Command Center:
                <?php echo htmlspecialchars($event['title']); ?>
            </h2>
            <a href="dashboard.php" class="btn btn-outline-dark">← Dashboard</a>
        </div>

        <?php if (isset($_GET['msg']) && $_GET['msg'] == 'sent') { ?>
        <div class="alert alert-danger fw-bold text-center">🚨 ALERT BROADCASTED!</div>
        <?php
}
elseif (isset($_GET['msg']) && $_GET['msg'] == 'cleared') { ?>
        <div class="alert alert-success fw-bold text-center">✅ Alert Cleared.</div>
        <?php
}?>

        <div class="row g-4">

            <div class="col-lg-6">

                <div class="card shadow p-4 border-danger border-2 mb-4">
                    <h5 class="text-danger fw-bold">📢 Emergency Broadcast</h5>
                    <?php if (!empty($event['alert_message'])) { ?>
                    <div class="alert alert-danger p-2 small text-center mb-3">
                        Active: <strong>
                            <?php echo htmlspecialchars($event['alert_message']); ?>
                        </strong>
                    </div>
                    <?php
}?>
                    <form method="POST">
                        <input type="text" name="alert_msg" class="form-control mb-2"
                            placeholder="e.g. Lunch is served!" required>
                        <div class="d-flex gap-2">
                            <button type="submit" name="broadcast_alert" class="btn btn-danger w-100">Broadcast</button>
                            <button type="submit" name="clear_alert" class="btn btn-outline-secondary">Clear</button>
                        </div>
                    </form>
                </div>

                <div class="card shadow p-4 border-0">
                    <h5 class="mb-3">👥 Event Staff</h5>
                    <form method="POST" class="d-flex gap-2 mb-3">
                        <input type="text" name="name" class="form-control form-control-sm" placeholder="Name" required>
                        <select name="role" class="form-select form-select-sm" style="width: 100px;">
                            <option value="Guard">Guard</option>
                            <option value="Support">Support</option>
                        </select>
                        <button type="submit" name="add_staff" class="btn btn-primary btn-sm">+</button>
                    </form>
                    <ul class="list-group list-group-flush">
                        <?php if (count($staff_list) > 0) {
    foreach ($staff_list as $s) { ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <div>
                                <strong>
                                    <?php echo htmlspecialchars($s['name']); ?>
                                </strong> <small class="text-muted">(
                                    <?php echo $s['role']; ?>)
                                </small>
                                <br><span class="badge bg-dark">
                                    <?php echo $s['access_token']; ?>
                                </span>
                            </div>
                            <a href="?id=<?php echo $event_id; ?>&delete_staff=<?php echo $s['id']; ?>"
                                class="text-danger text-decoration-none">×</a>
                        </li>
                        <?php
    }
}?>
                    </ul>
                </div>
            </div>

            <div class="col-lg-6">

                <div class="card shadow border-primary border-2 mb-4">
                    <div class="card-body text-center p-5">
                        <h3 class="mb-3">📊 Participants</h3>
                        <p class="text-muted mb-4">View the full list, check attendance status, and download data to
                            Excel.</p>
                        <a href="participants.php?event_id=<?php echo $event_id; ?>"
                            class="btn btn-primary btn-lg w-100 fw-bold">
                            View & Manage Participants →
                        </a>
                    </div>
                </div>

                <div class="card shadow p-4 border-0">
                    <h5 class="mb-3">🔗 Quick Links</h5>
                    <div class="d-grid gap-2">
                        <a href="guard_login.php" target="_blank" class="btn btn-outline-dark btn-sm text-start">🛡️
                            Guard Login Page</a>
                        <a href="event_details.php?id=<?php echo $event_id; ?>" target="_blank"
                            class="btn btn-outline-primary btn-sm text-start">🌍 Public Event Page</a>
                    </div>
                </div>

            </div>

        </div>
    </div>
</body>

</html>