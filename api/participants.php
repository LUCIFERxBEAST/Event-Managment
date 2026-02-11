<?php
// participants.php - FINAL WORKING VERSION (No Date Column)
mysqli_report(MYSQLI_REPORT_OFF);
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
include '../config/db.php';

// 1. Security
if (!isset($_SESSION['user_id']) || !isset($_GET['event_id'])) {
    die("⛔ Access Denied. <a href='dashboard.php'>Back to Dashboard</a>");
}

$event_id = intval($_GET['event_id']);
$user_id = $_SESSION['user_id'];

// 2. Check Ownership
$stmt = $pdo->prepare("SELECT * FROM hackathons WHERE id = ? AND created_by = ?");
$stmt->execute([$event_id, $user_id]);
$event = $stmt->fetch();

if (!$event) {
    die("⛔ Access Denied: You do not own this event.");
}

// 3. FETCH PARTICIPANTS (Without Date to prevent crashing)
// We order by 'r.id' which always exists
$sql = "SELECT u.name, u.email, r.status 
        FROM registrations r 
        JOIN users u ON r.user_id = u.id 
        WHERE r.hackathon_id = ? 
        ORDER BY r.id DESC";

$stmt_p = $pdo->prepare($sql);
$stmt_p->execute([$event_id]);
$participants = $stmt_p->fetchAll(); // Fetch all to count

$count = count($participants);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <title>Participants:
        <?php echo htmlspecialchars($event['title']); ?>
    </title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .status-present {
            color: #0f5132;
            background-color: #d1e7dd;
            padding: 4px 8px;
            border-radius: 6px;
            font-weight: bold;
        }

        .status-reg {
            color: #856404;
            background-color: #fff3cd;
            padding: 4px 8px;
            border-radius: 6px;
        }
    </style>
</head>

<body class="bg-light">

    <div class="container my-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2>📊 Participants</h2>
                <h5 class="text-muted">
                    <?php echo htmlspecialchars($event['title']); ?>
                </h5>
            </div>
            <div>
                <a href="manage_event.php?id=<?php echo $event_id; ?>" class="btn btn-outline-secondary me-2">← Back</a>
                <a href="export_participants.php?event_id=<?php echo $event_id; ?>"
                    class="btn btn-success fw-bold">📥 Download Excel</a>
            </div>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">Name</th>
                                <th>Email</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($count > 0): ?>
                            <?php foreach ($participants as $p): ?>
                            <tr>
                                <td class="ps-4 fw-bold">
                                    <?php echo htmlspecialchars($p['name']); ?>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($p['email']); ?>
                                </td>
                                <td>
                                    <?php if ($p['status'] == 'Present'): ?>
                                    <span class="status-present">✅ Present</span>
                                    <?php
        else: ?>
                                    <span class="status-reg">Registered</span>
                                    <?php
        endif; ?>
                                </td>
                            </tr>
                            <?php
    endforeach; ?>
                            <?php
else: ?>
                            <tr>
                                <td colspan="3" class="text-center py-5 text-muted">
                                    <h4>No participants yet.</h4>
                                </td>
                            </tr>
                            <?php
endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

</body>

</html>