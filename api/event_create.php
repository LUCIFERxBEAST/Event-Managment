<?php
session_start();
include __DIR__ . '/../config/db.php';

// 1. Security Check with Persistent Auth (Vercel-compatible)
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


// 2. Handle Form Submission
if (isset($_POST['create_event'])) {
    $title = $conn->real_escape_string($_POST['title']);
    $start = $_POST['start_date'] . ' ' . $_POST['start_time']; // Combine Date+Time
    $end = $_POST['end_date'] . ' ' . $_POST['end_time'];
    $venue = $conn->real_escape_string($_POST['venue']);
    $desc = $conn->real_escape_string($_POST['description']);
    $creator_id = $_SESSION['user_id'];

    // Process Tags (Array to String)
    $tags = isset($_POST['tags']) ? implode(",", $_POST['tags']) : "";

    // Insert into Database
    $sql = "INSERT INTO hackathons (created_by, title, event_start, event_end, venue, description, event_tags) 
            VALUES (:creator, :title, :start, :end, :venue, :desc, :tags)";

    $stmt = $conn->prepare($sql);

    try {
        if ($stmt->execute([
        'creator' => $creator_id,
        'title' => $title,
        'start' => $start,
        'end' => $end,
        'venue' => $venue,
        'desc' => $desc,
        'tags' => $tags
        ])) {
            echo "<script>alert('✅ Event Created Successfully!'); window.location.href = 'dashboard.php';</script>";
        }
    }
    catch (PDOException $e) {
        $error = "Error: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Event | HackHub</title>
    <!-- Google Fonts: Outfit -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .custom-checkbox {
            display: none;
        }

        .custom-label {
            display: inline-block;
            padding: 8px 16px;
            margin: 5px;
            border: 2px solid var(--primary-color);
            color: var(--primary-color);
            border-radius: 50px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
        }

        .custom-checkbox:checked+.custom-label {
            background: var(--primary-color);
            color: white;
            box-shadow: 0 4px 10px rgba(108, 99, 255, 0.3);
        }
    </style>
</head>

<body>

    <?php include __DIR__ . '/../includes/header.php'; ?>

    <div class="container my-5 fade-in" style="max-width: 800px;">
        <div class="glass-card">

            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                <h3 style="margin: 0;">🎤 Host a New Hackathon</h3>
                <a href="dashboard.php" class="btn btn-secondary" style="font-size: 0.9rem;">Cancel</a>
            </div>

            <?php if (isset($error))
    echo "<div class='alert alert-danger'>$error</div>"; ?>

            <form method="POST">

                <div class="form-group">
                    <label class="form-label">Event Title</label>
                    <div style="display: flex; gap: 10px;">
                        <input type="text" name="title" id="eventTitle" class="form-control"
                            placeholder="e.g. AI Revolution 2026" required>
                        <button type="button" class="btn btn-warning" onclick="magicFill()"
                            style="white-space: nowrap;">✨ Auto-Fill</button>
                    </div>
                    <small style="color: #888; font-size: 0.85rem; margin-top: 5px; display: block;">Type a title and
                        click 'Auto-Fill' to let AI write the rest!</small>
                </div>

                <div class="grid-3" style="grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.5rem;">
                    <div>
                        <label class="form-label">Starts</label>
                        <div style="display: flex; gap: 5px;">
                            <input type="date" name="start_date" class="form-control" required>
                            <input type="time" name="start_time" class="form-control" required>
                        </div>
                    </div>
                    <div>
                        <label class="form-label">Ends</label>
                        <div style="display: flex; gap: 5px;">
                            <input type="date" name="end_date" class="form-control" required>
                            <input type="time" name="end_time" class="form-control" required>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Venue / Location</label>
                    <input type="text" name="venue" id="venue" class="form-control" placeholder="e.g. Main Auditorium"
                        required>
                </div>

                <div class="form-group">
                    <label class="form-label mb-2">Target Audience (Select Tags)</label>
                    <div>
                        <input type="checkbox" class="custom-checkbox" name="tags[]" value="Web Dev" id="t1">
                        <label class="skill-label" for="t1">Web Dev</label>

                        <input type="checkbox" class="skill-check" name="tags[]" value="App Dev" id="t2">
                        <label class="skill-label" for="t2">App Dev</label>

                        <input type="checkbox" class="skill-check" name="tags[]" value="AI/ML" id="t3">
                        <label class="skill-label" for="t3">AI/ML</label>

                        <input type="checkbox" class="skill-check" name="tags[]" value="Blockchain" id="t4">
                        <label class="skill-label" for="t4">Blockchain</label>

                        <input type="checkbox" class="skill-check" name="tags[]" value="Design" id="t5">
                        <label class="skill-label" for="t5">Design</label>

                        <input type="checkbox" class="skill-check" name="tags[]" value="Cybersecurity" id="t6">
                        <label class="skill-label" for="t6">Security</label>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Description & Rules</label>
                    <textarea name="description" id="description" class="form-control" rows="6" required></textarea>
                </div>

                <button type="submit" name="create_event" class="btn btn-success"
                    style="width: 100%; padding: 1rem; font-size: 1.1rem; box-shadow: 0 4px 15px rgba(46, 204, 113, 0.4);">🚀
                    Launch Event</button>
            </form>

        </div>
    </div>

    <script>
        function magicFill() {
            let title = document.getElementById('eventTitle').value;
            if (title.length < 3) { alert("Please enter a title first!"); return; }

            let descField = document.getElementById('description');
            let venueField = document.getElementById('venue');

        // 1. GENERATE DESCRIPTION 