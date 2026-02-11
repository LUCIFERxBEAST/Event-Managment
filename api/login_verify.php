<?php
session_start();
include '../config/db.php';

// Security: Kick out if they didn't pass Step 1
if (!isset($_SESSION['temp_login_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['temp_login_id'];
$error = "";

if (isset($_POST['verify'])) {
    $entered_otp = $_POST['otp'];

    // 1. GET CURRENT OTP STATUS FROM DB
    $stmt = $conn->prepare("SELECT name, skills, otp_code, otp_expiry, otp_failed_attempts FROM users WHERE id = :id");
    $stmt->execute(['id' => $user_id]);
    $user = $stmt->fetch();

    $current_time = date("Y-m-d H:i:s");

    // 🛠️ RULE 1: MAX ATTEMPTS CHECK (Limit: 3)
    if ($user['otp_failed_attempts'] >= 3) {
        $error = "⛔ Account Locked! You entered the wrong OTP too many times. Please login again to generate a new code.";
    }
    // 🛠️ RULE 2: EXPIRY CHECK (Limit: 10 Mins)
    elseif ($current_time > $user['otp_expiry']) {
        $error = "⏳ OTP Expired! This code was only valid for 10 minutes. Please login again.";
    }
    // 🛠️ RULE 3: CODE MATCH CHECK
    elseif ($entered_otp === $user['otp_code']) {
        // ✅ SUCCESS!

        // Login the user fully
        $_SESSION['user_id'] = $user_id;
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_skills'] = $user['skills'];

        // Cleanup: Remove OTP from DB so it can't be used twice
        $conn->query("UPDATE users SET otp_code=NULL, otp_failed_attempts=0 WHERE id=$user_id");

        // Remove temp session
        unset($_SESSION['temp_login_id']);
        unset($_SESSION['temp_email']);

        header("Location: dashboard.php");
        exit();
    }
    else {
        // ❌ WRONG OTP
        // Increment failure counter
        $conn->query("UPDATE users SET otp_failed_attempts = otp_failed_attempts + 1 WHERE id=$user_id");

        $attempts_left = 2 - $user['otp_failed_attempts'];
        $error = "❌ Invalid Code! You have $attempts_left attempts remaining.";

        if ($attempts_left < 0) {
            // Force logout if limit hit
            session_destroy();
            header("Location: login.php?error=locked");
            exit();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <title>Verify Login</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-white d-flex align-items-center justify-content-center" style="height: 100vh;">

    <div class="text-center" style="max-width: 350px;">
        <h2 class="fw-bold">🛡️ Two-Step Auth</h2>
        <p class="text-muted">Enter the code sent to your email.<br>It expires in <strong>10 minutes</strong>.</p>

        <?php if ($error)
    echo "<div class='alert alert-danger'>$error</div>"; ?>

        <form method="POST">
            <div class="mb-3">
                <input type="number" name="otp" class="form-control text-center fs-2 fw-bold letter-spacing-2"
                    placeholder="000000" autofocus required>
            </div>
            <button type="submit" name="verify" class="btn btn-primary w-100 btn-lg">Verify & Enter</button>
        </form>

        <div class="mt-4">
            <a href="login.php" class="text-muted small text-decoration-none">← Back to Login</a>
        </div>
    </div>

    <?php if (isset($_GET['simulated_otp'])): ?>
    <script>
        setTimeout(function () {
            alert("👨‍💻 [DEVELOPER ALERT]\n\nYour Login OTP is: <?php echo $_GET['simulated_otp']; ?>\n\n(It expires in 10 mins!)");
        }, 500);
    </script>
    <?php
endif; ?>

</body>

</html>