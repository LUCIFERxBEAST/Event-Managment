<?php
session_start();
include __DIR__ . '/../config/db.php';

// 1. Security Check with Persistent Auth
if (!isset($_SESSION['user_id'])) {
    // Try to restore session from auth_token cookie
    if (isset($_COOKIE['auth_token'])) {
        try {
            $token_hash = hash('sha256', $_COOKIE['auth_token']);

            $stmt = $conn->prepare("SELECT id, name, skills FROM users WHERE auth_token = :token");
            $stmt->execute(['token' => $token_hash]);

            if ($user = $stmt->fetch()) {
                // Restore session from valid token
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_skills'] = explode(',', $user['skills']);
            }
            else {
                // Invalid/expired token - clear it and redirect
                setcookie('auth_token', '', time() - 3600, '/');
                header("Location: login.php?error=invalid_token");
                exit();
            }
        }
        catch (PDOException $e) {
            // Database error (likely auth_token column doesn't exist)
            // Log error and redirect to login
            error_log("Auth token error: " . $e->getMessage());
            setcookie('auth_token', '', time() - 3600, '/');
            header("Location: login.php?error=db_error");
            exit();
        }
    }
    else {
        // No session, no cookie - must login
        header("Location: login.php");
        exit();
    }
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];
$user_skills = is_array($_SESSION['user_skills']) ? $_SESSION['user_skills'] : explode(',', $_SESSION['user_skills']);

// 2. HOSTING QUERY
$hosting = $conn->query("SELECT * FROM hackathons WHERE created_by = $user_id ORDER BY event_start ASC");

// 3. ATTENDING QUERY (Make sure to fetch event_end for the certificate logic)
$attending_query = "SELECT h.*, r.status, r.qr_code_hash 
                    FROM hackathons h 
                    JOIN registrations r ON h.id = r.hackathon_id 
                    WHERE r.user_id = $user_id";
$attending = $conn->query($attending_query);

// 4. RECOMMENDATION QUERY
// 4. RECOMMENDATION QUERY (Fix: Use PDO Prepared Statements)
$params = [];
$skill_conditions = [];

// Base Query
$recommend_sql = "SELECT * FROM hackathons WHERE created_by != ? AND id NOT IN (SELECT hackathon_id FROM registrations WHERE user_id = ?)";
$params[] = $user_id;
$params[] = $user_id;

// Dynamic Skill Filters
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

$stmt = $conn->prepare($recommend_sql);
$stmt->execute($params);
$recommendations = $stmt;
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <title>Dashboard | HackHub</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <!-- Google Fonts: Outfit -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body>

    <?php include __DIR__ . '/../includes/header.php'; ?>

    <div class="container my-5 fade-in">

        <!-- Custom Tabs -->
        <div class="tab-nav">
            <button class="tab-btn active" onclick="openTab('attending')">🎫 My Tickets</button>
            <button class="tab-btn" onclick="openTab('hosting')">🎤 Hosting</button>
            <button class="tab-btn" onclick="openTab('explore')">🌍 Explore</button>
        </div>

        <!-- Content Sections -->
        <div id="attending" class="tab-content active">
            <h3 class="mb-4">My Upcoming Events</h3>
            <div class="grid-3">
                <?php if ($attending->rowCount() > 0): ?>
                <?php while ($row = $attending->fetch()): ?>
                <?php
        $is_present = ($row['status'] == 'Present');
        $is_ended = (time() > strtotime($row['event_end']));
        $status_bg = $is_present ? 'bg-success' : 'bg-warning';
?>
                <div class="glass-card">
                    <div class="flex-between mb-3">
                        <h5 style="margin: 0;">
                            <?php echo htmlspecialchars($row['title']); ?>
                        </h5>
                        <span class="badge <?php echo $status_bg; ?>">
                            <?php echo $row['status']; ?>
                        </span>
                    </div>
                    <p class="text-muted mb-4 text-sm">
                        📍
                        <?php echo htmlspecialchars($row['venue']); ?>
                    </p>

                    <div style="display: grid; gap: 0.8rem;">
                        <a href="ticket.php?hash=<?php echo $row['qr_code_hash']; ?>" class="btn btn-primary w-100">
                            View QR Ticket
                        </a>

                        <?php if ($is_present && $is_ended): ?>
                        <a href="generate_certificate.php?event_id=<?php echo $row['id']; ?>"
                            class="btn btn-success w-100">
                            🎓 Download Certificate
                        </a>
                        <?php
        elseif ($is_present && !$is_ended): ?>
                        <button class="btn btn-secondary w-100" disabled style="opacity: 0.7;">
                            ⏳ Cert. available after event
                        </button>
                        <?php
        else: ?>
                        <button class="btn btn-secondary w-100" disabled style="opacity: 0.7;">
                            🔒 Attend to Unlock Cert.
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
                    <p class="mb-3">You haven't joined any hackathons yet.</p>
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
                <?php if ($hosting->rowCount() > 0): ?>
                <?php while ($row = $hosting->fetch()): ?>
                <div class="glass-card event-card-border">
                    <h5 class="mb-2">
                        <?php echo htmlspecialchars($row['title']); ?>
                    </h5>
                    <p class="text-muted mb-4 text-sm">
                        📅
                        <?php echo date('d M Y, h:i A', strtotime($row['event_start'])); ?>
                    </p>
                    <a href="manage_event.php?id=<?php echo $row['id']; ?>" class="btn btn-secondary w-100">
                        ⚙️ Manage & Guards
                    </a>
                </div>
                <?php
    endwhile; ?>
                <?php
else: ?>
                <div class="glass-panel p-4 text-center" style="grid-column: 1 / -1;">
                    <p class="mb-3">You haven't created any events.</p>
                    <a href="event_create.php" class="btn btn-primary">Create Your First Hackathon</a>
                </div>
                <?php
endif; ?>
            </div>
        </div>

        <div id="explore" class="tab-content">
            <h3 class="mb-4">Recommended for You ✨</h3>
            <div class="grid-3">
                <?php if ($recommendations->rowCount() > 0): ?>
                <?php while ($row = $recommendations->fetch()): ?>
                <div class="glass-card">
                    <h5 class="mb-2">
                        <?php echo htmlspecialchars($row['title']); ?>
                    </h5>
                    <p class="text-muted mb-3" style="font-size: 0.9rem;">
                        <?php echo date('M d', strtotime($row['event_start'])); ?> |
                        <?php echo htmlspecialchars($row['venue']); ?>
                    </p>
                    <div class="mb-3">
                        <?php
        $tags = explode(',', $row['event_tags']);
        foreach ($tags as $tag) {
            if (trim($tag) != '')
                echo "<span class='badge bg-secondary' style='margin-right: 4px; font-size: 0.7rem;'>$tag</span>";
        }
?>
                    </div>
                    <a href="event_details.php?id=<?php echo $row['id']; ?>" class="btn btn-primary"
                        style="width: 100%;">
                        Details & Register
                    </a>
                </div>
                <?php
    endwhile; ?>
                <?php
else: ?>
                <p class="text-center text-muted mt-4" style="grid-column: 1 / -1;">
                    No recommendations found matching your skills right now.
                </p>
                <?php
endif; ?>
            </div>
        </div>

    </div>

    <!-- Emergency Overlay -->
    <div id="emergency-overlay"
        style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: 10000; align-items: center; justify-content: center;">
        <div class="emergency-box text-center">
            <h1 style="font-size: 50px; margin-bottom: 1rem;">📢</h1>
            <h2 style="color: var(--danger-color); font-weight: 800; margin-bottom: 1rem;">ANNOUNCEMENT</h2>
            <h5 id="alert-event-name" class="text-muted mb-4">Event Name</h5>
            <p id="alert-text" style="font-size: 1.4rem; font-weight: 600; margin-bottom: 2rem; color: #333;">Loading
                message...</p>
            <button onclick="closeAlert()" class="btn btn-danger"
                style="width: 100%; padding: 1rem;">Acknowledged</button>
        </div>
    </div>

    <script>
        // Tab Logic
        function openTab(tabName) {
            var i;
            var x = document.getElementsByClassName("tab-content");
            var btns = document.getElementsByClassName("tab-btn");
            for (i = 0; i < x.length; i++) {
                x[i].style.display = "none";
                x[i].classList.remove("active");
            }
            for (i = 0; i < btns.length; i++) {
                btns[i].classList.remove("active");
            }
            document.getElementById(tabName).style.display = "block";
            document.getElementById(tabName).classList.add("active");

            let targetBtn = document.querySelector(`button[onclick="openTab('${tabName}')"]`);
            if (targetBtn) targetBtn.classList.add("active");
        }
    </script>
    <script>
        let myEventIds = [
             <?php
$attending_check = $conn->query("SELECT hackathon_id FROM registrations WHERE user_id = $user_id");
$ids = [];
if ($attending_check) {
    while ($r = $attending_check->fetch()) {
        $ids[] = $r['hackath                ho implode(',', $ids);
?>
        ];


        let lastMessage = "";

        function checkForEmergency() {
            if (myEventIds.length === 0) return;

            fetch('api/check_alerts.php?ids=' + myEventIds.join(','))
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'alert') {
                        if (data.message !== lastMessage) {
                            lastMessage = data.message;
                            showAlert(data.event, data.message);
                        }
                    } else {
                        lastMessage = "";
                    }
                })
                .catch(err => console.error("Polling error:", err));
        }