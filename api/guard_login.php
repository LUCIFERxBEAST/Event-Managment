<?php
session_start();
include '../config/db.php';

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $token = $_POST['access_token'];

    // 1. Find the Guard and the Event they belong to
    // We use the 'event_staff' table we created earlier
    $sql = "SELECT s.id, s.name, s.hackathon_id, h.title 
            FROM event_staff s 
            JOIN hackathons h ON s.hackathon_id = h.id 
            WHERE s.access_token = ?";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$token]);
    $row = $stmt->fetch();

    if ($row) {
        // 2. Save everything to Session (Like your old code did)
        $_SESSION['guard_id'] = $row['id'];
        $_SESSION['guard_name'] = $row['name'];
        $_SESSION['event_id'] = $row['hackathon_id']; // <--- CRITICAL: Locks scanner to this event
        $_SESSION['event_title'] = $row['title'];

        header("Location: guard_scanner.php");
        exit();
    }
    else {
        $error = "❌ Invalid Access Token.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <title>Guard Login</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-dark text-white d-flex align-items-center justify-content-center" style="height: 100vh;">
    <div class="card bg-secondary p-4 text-center" style="max-width: 400px; width: 100%;">
        <h3>🛡️ Guard Access</h3>
        <p class="mb-4">Enter the Token provided by the Organizer</p>

        <?php if ($error)
    echo "<div class='alert alert-danger'>$error</div>"; ?>

        <form method="POST">
            <input type="text" name="access_token" class="form-control form-control-lg text-center mb-3"
                placeholder="e.g. G-1234" required>
            <button type="submit" class="btn btn-warning w-100 fw-bold">Login & Start Scanning</button>
        </form>
    </div>
</body>

</html>