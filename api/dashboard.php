<?php
// dashboard.php
session_start();
include __DIR__ . '/../config/db.php';

// 1. Security Check
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];
$user_skills = isset($_SESSION['user_skills']) ? explode(',', $_SESSION['user_skills']) : [];

// 2. HOSTING QUERY (Events I Created)
$stmt = $pdo->prepare("SELECT * FROM hackathons WHERE created_by = ? ORDER BY event_start ASC");
$stmt->execute([$user_id]);
$hosting = $stmt->fetchAll();

// 3. ATTENDING QUERY (Events I Joined)
$attending_sql = "SELECT h.*, r.status, r.qr_code_hash 
                    FROM hackathons h 
                    JOIN registrations r ON h.id = r.hackathon_id 
                    WHERE r.user_id = ?";
$stmt_agg = $pdo->prepare($attending_sql);
$stmt_agg->execute([$user_id]);
$attending = $stmt_agg->fetchAll();

// 4. RECOMMENDATIONS
$skill_filters = [];
if (!empty($user_skills)) {
    foreach ($user_skills as $skill) {
        $clean_skill = addslashes(trim($skill));
        if (!empty($clean_skill))
            $skill_filters[] = "event_tags LIKE '%$clean_skill%'";
    }
}
$recommend_sql = "SELECT * FROM hackathons 
                  WHERE created_by != ? 
                  AND id NOT IN (SELECT hackathon_id FROM registrations WHERE user_id = ?)";
if (!empty($skill_filters)) {
    $recommend_sql .= " AND (" . implode(" OR ", $skill_filters) . ")";
}
$recommend_sql .= " LIMIT 6";

$stmt_rec = $pdo->prepare($recommend_sql);
$stmt_rec->execute([$user_id, $user_id]);
$recommendations = $stmt_rec->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <title>Dashboard | Hackathon Hub</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        /* Sticky Footer & Layout Fixes */
        body {
            background-color: #f8f9fa;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        .container.main-content {
            flex: 1;
        }

        footer {
            margin-top: auto;
            width: 100%;
        }

        /* UI Elements */
        .nav-pills .nav-link {
            border-radius: 20px;
            padding: 10px 20px;
            color: #555;
        }

        .nav-pills .nav-link.active {
            background-color: #0d6efd;
            color: white;
            font-weight: bold;
        }

        .card {
            border: none;
            border-radius: 15px;
            transition: transform 0.2s;
        }

        .card:hover {
            transform: translateY(-3px);
        }

        .status-registered {
            background: #fff3cd;
            color: #856404;
        }

        .status-present {
            background: #d1e7dd;
            color: #0f5132;
        }
    </style>
</head>

<body>

    <nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold" href="#">🚀 HackHub</a>
            <div class="d-flex align-items-center">
                <span class="text-white me-2 d-none d-sm-block">Hi,
                    <?php echo htmlspecialchars($user_name); ?>
                </span>
                <a href="logout.php" class="btn btn-sm btn-light text-primary fw-bold">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container main-content my-4">

        <ul class="nav nav-pills mb-4 justify-content-center" id="pills-tab" role="tablist">
            <li class="nav-item"><button class="nav-link active" data-bs-toggle="pill" data-bs-target="#attending">🎫 My
                    Tickets</button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#hosting">🎤
                    Hosting</button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#explore">🌍
                    Explore</button></li>
        </ul>

        <div class="tab-content">

            <div class="tab-pane fade show active" id="attending">
                <h4 class="mb-3">My Upcoming Events</h4>
                <div class="row g-3">
                    <?php if (count($attending) > 0): ?>
                    <?php foreach ($attending as $row): ?>
                    <?php
        $is_present = ($row['status'] == 'Present');
        $event_end = isset($row['event_end']) ? $row['event_end'] : 'tomorrow';
        $is_ended = (time() > strtotime($event_end));
?>
                    <div class="col-md-4">
                        <div class="card shadow-sm p-3 h-100">
                            <div class="d-flex justify-content-between align-items-start">
                                <h5 class="fw-bold mb-1">
                                    <?php echo htmlspecialchars($row['title']); ?>
                                </h5>
                                <span class="badge <?php echo $is_present ? 'status-present' : 'status-registered'; ?>">
                                    <?php echo $row['status']; ?>
                                </span>
                            </div>
                            <p class="text-muted small mb-3">📍
                                <?php echo htmlspecialchars($row['venue']); ?>
                            </p>
                            <div class="mt-auto d-grid gap-2">
                                <a href="ticket.php?hash=<?php echo $row['qr_code_hash']; ?>"
                                    class="btn btn-primary btn-sm">View QR Ticket</a>
                                <?php if ($is_present && $is_ended): ?>
                                <a href="generate_certificate.php?event_id=<?php echo $row['id']; ?>"
                                    class="btn btn-success btn-sm fw-bold">🎓 Download Certificate</a>
                                <?php
        elseif ($is_present && !$is_ended): ?>
                                <button class="btn btn-warning btn-sm text-dark" disabled>⏳ Cert. available after
                                    event</button>
                                <?php
        else: ?>
                                <button class="btn btn-secondary btn-sm" disabled>🔒 Attend to Unlock Cert.</button>
                                <?php
        endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php
    endforeach; ?>
                    <?php
else: ?>
                    <div class="text-center py-5">
                        <p class="text-muted">No events joined yet.</p>
                    </div>
                    <?php
endif; ?>
                </div>
            </div>

            <div class="tab-pane fade" id="hosting">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4>Events I'm Organizing</h4>
                    <a href="event_create.php" class="btn btn-success fw-bold">+ Create New</a>
                </div>
                <div class="row g-3">
                    <?php if (count($hosting) > 0): ?>
                    <?php foreach ($hosting as $row): ?>
                    <div class="col-md-4">
                        <div class="card shadow-sm p-3 border-start border-4 border-success">
                            <h5 class="fw-bold">
                                <?php echo htmlspecialchars($row['title']); ?>
                            </h5>
                            <p class="small text-muted mb-2">📅
                                <?php echo date('d M Y, h:i A', strtotime($row['event_start'])); ?>
                            </p>
                            <div class="d-grid gap-2">
                                <a href="manage_event.php?id=<?php echo $row['id']; ?>"
                                    class="btn btn-outline-dark btn-sm">⚙️ Manage & Guards</a>
                            </div>
                        </div>
                    </div>
                    <?php
    endforeach; ?>
                    <?php
else: ?>
                    <div class="text-center py-5">
                        <p class="text-muted">You haven't created any events.</p>
                    </div>
                    <?php
endif; ?>
                </div>
            </div>

            <div class="tab-pane fade" id="explore">
                <h4 class="mb-3">Recommended for You ✨</h4>
                <div class="row g-3">
                    <?php if (count($recommendations) > 0): ?>
                    <?php foreach ($recommendations as $row): ?>
                    <div class="col-md-4">
                        <div class="card shadow-sm h-100">
                            <div class="card-body">
                                <h5 class="card-title fw-bold">
                                    <?php echo htmlspecialchars($row['title']); ?>
                                </h5>
                                <h6 class="card-subtitle mb-2 text-muted small">
                                    <?php echo htmlspecialchars($row['venue']); ?>
                                </h6>
                                <a href="event_details.php?id=<?php echo $row['id']; ?>"
                                    class="btn btn-primary w-100 mt-2">Details & Register</a>
                            </div>
                        </div>
                    </div>
                    <?php
    endforeach; ?>
                    <?php
else: ?>
                    <p class="text-center text-muted mt-4">No recommendations found.</p>
                    <?php
endif; ?>
                </div>
            </div>

        </div>
    </div>

    <footer class="text-center py-4 mt-5" style="border-top: 1px solid #e9ecef;">
        <div class="container">
            <p class="text-muted small mb-1">&copy;
                <?php echo date('Y'); ?> Hackathon Hub
            </p>
            <a href="guard_login.php" class="text-decoration-none text-secondary fw-bold" style="font-size: 13px;">🔒
                Staff / Guard Access</a>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <div id="emergency-overlay"
        style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(220, 53, 69, 0.95); z-index: 10000; align-items: center; justify-content: center; text-align: center; color: white;">
        <div style="background: white; color: black; padding: 30px; border-radius: 15px; width: 90%; max-width: 500px;">
            <h1 style="font-size: 50px;">📢</h1>
            <h2 class="fw-bold text-danger mb-3">ANNOUNCEMENT</h2>
            <h5 id="alert-event-name" class="text-muted mb-4">Event</h5>
            <p id="alert-text" class="fs-4 fw-bold mb-4">Loading...</p>
            <button onclick="document.getElementById('emergency-overlay').style.display='none'"
                class="btn btn-dark w-100">Close</button>
        </div>
    </div>

    <script>
        // Get IDs for polling
        let myEventIds = [
            <?php
$stmt_check = $pdo->prepare("SELECT hackathon_id FROM registrations WHERE user_id = ?");
$stmt_check->execute([$user_id]);
$ids = $stmt_check->fetchAll(PDO::FETCH_COLUMN);
echo implode(',', $ids);
?>
        ];
        let lastMsg = "";

        setInterval(() => {
            if (myEventIds.length === 0) return;
            fetch('check_alerts.php?ids=' + myEventIds.join(','))
                .then(r => r.json())
                .then(d => {
                    if (d.status === 'alert' && d.message !== lastMsg) {
                        lastMsg = d.message;
                        document.getElementById('alert-event-name').innerText = d.event;
                        document.getElementById('alert-text').innerText = d.message;
                        document.getElementById('emergency-overlay').style.display = 'flex';
                    }
                });
        }, 4000);
    </script>

</body>

</html>