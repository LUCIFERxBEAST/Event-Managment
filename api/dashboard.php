<?php
session_start();
include __DIR__ . '/../config/db.php';

// 1. Security Check with Persistent Auth
if (!isset($_SESSION['user_id'])) {
    if (isset($_COOKIE['auth_token'])) {
        try {
            $token_hash = hash('sha256', $_COOKIE['auth_token']);
            $stmt = $conn->prepare("SELECT id, name, skills FROM users WHERE auth_token = :token");
            $stmt->execute(['token' => $token_hash]);

            if ($user = $stmt->fetch()) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_skills'] = explode(',', $user['skills']);
            }
            else {
                setcookie('auth_token', '', time() - 3600, '/');
                header("Location: login.php?error=invalid_token");
                exit();
            }
        }
        catch (PDOException $e) {
            error_log("Auth token error: " . $e->getMessage());
            setcookie('auth_token', '', time() - 3600, '/');
            header("Location: login.php?error=db_error");
            exit();
        }
    }
    else {
        header("Location: login.php");
        exit();
    }
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];
$user_skills = is_array($_SESSION['user_skills'] ?? []) ? $_SESSION['user_skills'] : explode(',', $_SESSION['user_skills']);

// 2. HOSTING QUERY (Fixed with Prepared Statement)
$stmt_hosting = $conn->prepare("SELECT * FROM hackathons WHERE created_by = ? ORDER BY event_start ASC");
$stmt_hosting->execute([$user_id]);

// 3. ATTENDING QUERY (Fixed with Prepared Statement)
$attending_query = "SELECT h.*, r.status, r.qr_code_hash 
                    FROM hackathons h 
                    JOIN registrations r ON h.id = r.hackathon_id 
                    WHERE r.user_id = ?";
$stmt_attending = $conn->prepare($attending_query);
$stmt_attending->execute([$user_id]);

// 4. RECOMMENDATION QUERY
$params = [$user_id, $user_id];
$skill_conditions = [];
$recommend_sql = "SELECT * FROM hackathons WHERE created_by != ? AND id NOT IN (SELECT hackathon_id FROM registrations WHERE user_id = ?)";

if (!empty($user_skills)) {
    foreach ($user_skills as $skill) {
        $skill = trim($skill);
        if ($skill === '')
            continue;
        $skill_conditions[] = "event_tags LIKE ?";
        $params[] = "%" . $skill . "%";
    }
    if (!empty($skill_conditions)) {
        $recommend_sql .= " AND (" . implode(" OR ", $skill_conditions) . ")";
    }
}
$recommend_sql .= " LIMIT 6";
$stmt_rec = $conn->prepare($recommend_sql);
$stmt_rec->execute($params);

// Prep IDs for the JS Alert system
$my_registered_ids = [];
$stmt_ids = $conn->prepare("SELECT hackathon_id FROM registrations WHERE user_id = ?");
$stmt_ids->execute([$user_id]);
while ($r = $stmt_ids->fetch()) {
    $my_registered_ids[] = (int)$r['hackathon_id'];
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Dashboard | HackHub</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        #emergency-overlay {
            background: rgba(0, 0, 0, 0.85);
            backdrop-filter: blur(5px);
            display: none;
        }

        .emergency-box {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            max-width: 500px;
            width: 90%;
        }
    </style>
</head>

<body>

    <?php include __DIR__ . '/../includes/header.php'; ?>

    <div class="container my-5 fade-in">
        <div class="tab-nav">
            <button class="tab-btn active" onclick="openTab('attending')">🎫 My Tickets</button>
            <button class="tab-btn" onclick="openTab('hosting')">🎤 Hosting</button>
            <button class="tab-btn" onclick="openTab('explore')">🌍 Explore</button>
        </div>

        <div id="attending" class="tab-content active">
            <h3 class="mb-4">My Upcoming Events</h3>
            <div class="grid-3">
                <?php if ($stmt_attending->rowCount() > 0): ?>
                <?php while ($row = $stmt_attending->fetch()): ?>
                <?php
        $is_present = ($row['status'] == 'Present');
        $is_ended = (time() > strtotime($row['event_end']));
?>
                <div class="glass-card">
                    <div class="flex-between mb-3">
                        <h5 style="margin: 0;">
                            <?= htmlspecialchars($row['title'])?>
                        </h5>
                        <span class="badge <?= $is_present ? 'bg-success' : 'bg-warning'?>">
                            <?= $row['status']?>
                        </span>
                    </div>
                    <p class="text-muted mb-4 text-sm">📍
                        <?= htmlspecialchars($row['venue'])?>
                    </p>
                    <div style="display: grid; gap: 0.8rem;">
                        <a href="ticket.php?hash=<?= $row['qr_code_hash']?>" class="btn btn-primary w-100">View QR
                            Ticket</a>
                        <?php if ($is_present && $is_ended): ?>
                        <a href="generate_certificate.php?event_id=<?= $row['id']?>" class="btn btn-success w-100">🎓
                            Download Certificate</a>
                        <?php
        else: ?>
                        <button class="btn btn-secondary w-100" disabled style="opacity: 0.7;">
                            <?=!$is_present ? '🔒 Attend to Unlock' : '⏳ Post-Event Only'?>
                        </button>
                        <?php
        endif; ?>
                    </div>
                </div>
                <?php
    endwhile; ?>
                <?php
else: ?>
                <div class="glass-panel p-4 text-center" style="grid-column: 1 / -1;">
                    <p>You haven't joined any hackathons yet.</p>
                    <button class="btn btn-secondary" onclick="openTab('explore')">Find an Event</button>
                </div>
                <?php
endif; ?>
            </div>
        </div>

        <div id="hosting" class="tab-content">
            <div class="flex-between mb-4">
                <h3>Events I'm Organizing</h3>
                <a href="event_create.php" class="btn btn-success">+ Create New</a>
            </div>
            <div class="grid-3">
                <?php while ($row = $stmt_hosting->fetch()): ?>
                <div class="glass-card">
                    <h5>
                        <?= htmlspecialchars($row['title'])?>
                    </h5>
                    <p class="text-muted">📅
                        <?= date('d M Y', strtotime($row['event_start']))?>
                    </p>
                    <a href="manage_event.php?id=<?= $row['id']?>" class="btn btn-secondary w-100">⚙️ Manage &
                        Guards</a>
                </div>
                <?php
endwhile; ?>
            </div>
        </div>

        <div id="explore" class="tab-content">
            <h3 class="mb-4">Recommended for You ✨</h3>
            <div class="grid-3">
                <?php while ($row = $stmt_rec->fetch()): ?>
                <div class="glass-card">
                    <h5>
                        <?= htmlspecialchars($row['title'])?>
                    </h5>
                    <p class="text-muted">
                        <?= htmlspecialchars($row['venue'])?>
                    </p>
                    <div class="mb-3">
                        <?php foreach (explode(',', $row['event_tags']) as $tag): ?>
                        <?php if (trim($tag)): ?><span class='badge bg-secondary'>
                            <?= htmlspecialchars($tag)?>
                        </span>
                        <?php
        endif; ?>
                        <?php
    endforeach; ?>
                    </div>
                    <a href="event_details.php?id=<?= $row['id']?>" class="btn btn-primary w-100">Details &
                        Register</a>
                </div>
                <?php
endwhile; ?>
            </div>
        </div>
    </div>

    <div id="emergency-overlay"
        style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: 10000; align-items: center; justify-content: center;">
        <div class="emergency-box text-center">
            <h1 style="font-size: 50px;">📢</h1>
            <h2 style="color: red;">ANNOUNCEMENT</h2>
            <h5 id="alert-event-name">Event</h5>
            <p id="alert-text">Message content...</p>
            <button onclick="closeAlert()" class="btn btn-danger" style="width: 100%;">Acknowledged</button>
        </div>
    </div>

    <script>
        function openTab(tabName) {
            document.querySelectorAll(".tab-content").forEach(x => x.classList.remove("active"));
            document.querySelectorAll(".tab-btn").forEach(b => b.classList.remove("active"));
            document.getElementById(tabName).classList.add("active");
            event.currentTarget?.classList.add("active");
        }

        function showAlert(eventName, message) {
            document.getElementById('alert-event-name').innerText = eventName;
            document.getElementById('alert-text').innerText = message;
            document.getElementById('emergency-overlay').style.display = 'flex';
        }

        function closeAlert() {
            document.getElementById('emergency-overlay').style.display = 'none';
        }

        let myEventIds = <?= json_encode($my_registered_ids)?>;
        let lastMessage = "";

        function checkForEmergency() {
            if (myEventIds.length === 0) return;
            fetch('api/check_alerts.php?ids=' + myEventIds.join(','))
                .then(r => r.json())
                .then(data => {
                    if (data.status === 'alert' && data.message !== lastMessage) {
                        lastMessage = data.message;
                        showAlert(data.event, data.message);
                    }
                });
        }
        setInterval(checkForEmergency, 10000);
    </script>
</body>

</html>