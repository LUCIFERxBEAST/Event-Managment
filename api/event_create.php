<?php
session_start();
include __DIR__ . '/../config/db.php';

// 1. Security: Only logged-in users can create events
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// 2. Handle Form Submission
if (isset($_POST['create_event'])) {
    $title = $_POST['title'];
    $start = $_POST['start_date'] . ' ' . $_POST['start_time']; // Combine Date+Time
    $end = $_POST['end_date'] . ' ' . $_POST['end_time'];
    $venue = $_POST['venue'];
    $desc = $_POST['description'];
    $creator_id = $_SESSION['user_id'];

    // Process Tags (Array to String)
    $tags = isset($_POST['tags']) ? implode(",", $_POST['tags']) : "";

    // Insert into Database
    $sql = "INSERT INTO hackathons (created_by, title, event_start, event_end, venue, description, event_tags) 
            VALUES (?, ?, ?, ?, ?, ?, ?)";

    $stmt = $pdo->prepare($sql);

    if ($stmt->execute([$creator_id, $title, $start, $end, $venue, $desc, $tags])) {
        echo "<script>alert('✅ Event Created Successfully!'); window.location.href = 'dashboard.php';</script>";
    }
    else {
        $error = "Error: " . implode(" ", $stmt->errorInfo());
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <title>Create Event | Hackathon Hub</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">

    <div class="container my-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow p-4">

                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h3 class="fw-bold">🎤 Host a New Hackathon</h3>
                        <a href="dashboard.php" class="btn btn-outline-secondary btn-sm">Cancel</a>
                    </div>

                    <?php if (isset($error))
    echo "<div class='alert alert-danger'>$error</div>"; ?>

                    <form method="POST">

                        <div class="mb-3">
                            <label class="fw-bold">Event Title</label>
                            <div class="input-group">
                                <input type="text" name="title" id="eventTitle" class="form-control"
                                    placeholder="e.g. AI Revolution 2026" required>
                                <button type="button" class="btn btn-warning fw-bold" onclick="magicFill()">✨ Auto-Fill
                                    Details</button>
                            </div>
                            <small class="text-muted">Type a title and click 'Auto-Fill' to let AI write the
                                rest!</small>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="fw-bold">Starts</label>
                                <div class="input-group">
                                    <input type="date" name="start_date" class="form-control" required>
                                    <input type="time" name="start_time" class="form-control" required>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="fw-bold">Ends</label>
                                <div class="input-group">
                                    <input type="date" name="end_date" class="form-control" required>
                                    <input type="time" name="end_time" class="form-control" required>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="fw-bold">Venue / Location</label>
                            <input type="text" name="venue" id="venue" class="form-control"
                                placeholder="e.g. Main Auditorium" required>
                        </div>

                        <div class="mb-3">
                            <label class="fw-bold d-block mb-2">Target Audience (Select Tags)</label>
                            <div class="btn-group-toggle" data-toggle="buttons">

                                <input type="checkbox" class="btn-check" name="tags[]" value="Web Dev" id="t1">
                                <label class="btn btn-outline-primary btn-sm rounded-pill m-1" for="t1">Web Dev</label>

                                <input type="checkbox" class="btn-check" name="tags[]" value="App Dev" id="t2">
                                <label class="btn btn-outline-primary btn-sm rounded-pill m-1" for="t2">App Dev</label>

                                <input type="checkbox" class="btn-check" name="tags[]" value="AI/ML" id="t3">
                                <label class="btn btn-outline-primary btn-sm rounded-pill m-1" for="t3">AI/ML</label>

                                <input type="checkbox" class="btn-check" name="tags[]" value="Blockchain" id="t4">
                                <label class="btn btn-outline-primary btn-sm rounded-pill m-1"
                                    for="t4">Blockchain</label>

                                <input type="checkbox" class="btn-check" name="tags[]" value="Design" id="t5">
                                <label class="btn btn-outline-primary btn-sm rounded-pill m-1" for="t5">Design</label>

                                <input type="checkbox" class="btn-check" name="tags[]" value="Cybersecurity" id="t6">
                                <label class="btn btn-outline-primary btn-sm rounded-pill m-1" for="t6">Security</label>

                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="fw-bold">Description & Rules</label>
                            <textarea name="description" id="description" class="form-control" rows="6"
                                required></textarea>
                        </div>

                        <button type="submit" name="create_event" class="btn btn-success w-100 py-2 fw-bold">🚀 Launch
                            Event</button>
                    </form>

                </div>
            </div>
        </div>
    </div>

    <script>
        function magicFill() {
            let title = document.getElementById('eventTitle').value;
            if (title.length < 3) { alert("Please enter a title first!"); return; }

            let descField = document.getElementById('description');
            let venueField = document.getElementById('venue');

            // 1. GENERATE DESCRIPTION based on Title keywords
            let aiText = "👋 Welcome to " + title + "!\n\n";
            aiText += "🚀 **About the Event:**\nJoin us for an intense 24-hour hackathon where innovation meets execution. Whether you are a coder, designer, or thinker, this is your platform to shine.\n\n";

            // Smart Context Detection
            if (title.toLowerCase().includes("ai")) {
                aiText += "🤖 **Theme:** Artificial Intelligence & Future Tech.\n";
                document.getElementById('t3').checked = true; // Auto-check AI tag
            } else if (title.toLowerCase().includes("web")) {
                aiText += "🌐 **Theme:** Full Stack Web Development.\n";
                document.getElementById('t1').checked = true; // Auto-check Web tag
            } else if (title.toLowerCase().includes("design")) {
                aiText += "🎨 **Theme:** UI/UX and Creative Design.\n";
                document.getElementById('t5').checked = true; // Auto-check Design tag
            }

            aiText += "\n🏆 **Prizes:**\n- 1st Place: $1000 + Internship\n- 2nd Place: $500\n- 3rd Place: Swag Kits\n\n";
            aiText += "🍕 **Perks:** Free Food, Red Bull, and Wi-Fi.\n";
            aiText += "📅 **Schedule:** Opening Ceremony at 9:00 AM. Coding starts at 10:00 AM.";

            // Typewriter Effect for "AI" feel
            descField.value = "";
            let i = 0;
            let speed = 10;
            function typeWriter() {
                if (i < aiText.length) {
                    descField.value += aiText.charAt(i);
                    i++;
                    setTimeout(typeWriter, speed);
                }
            }
            typeWriter();

            // 2. Auto-Fill Venue if empty
            if (venueField.value === "") venueField.value = "Tech Park Auditorium, Hall A";
        }
    </script>

</body>

</html>