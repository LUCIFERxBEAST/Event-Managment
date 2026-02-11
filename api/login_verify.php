<?php
session_start();
include __DIR__ . '/../config/db.php';

// Security: Kick out if no email param
if (!isset($_GET['email'])) {
    header("Location: login.php");
    exit();
}

$email = $_GET['email'];
$error = "";

// FETCH USER CONTEXT
$stmt = $conn->prepare("SELECT id, name, skills, otp_code, otp_expiry, otp_failed_attempts FROM users WHERE email = :email");
$stmt->execute(['email' => $email]);
$user = $stmt->fetch();

if (!$user) {
    header("Location: login.php?error=invalid_email");
    exit();
}

$user_id = $user['id'];

if (isset($_POST['verify'])) {
    $entered_otp = trim($_POST['otp']);

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
    elseif ($entered_otp == $user['otp_code']) {
        // ✅ SUCCESS!

        // 1. SESSION LOGIN (Standard)
        $_SESSION['user_id'] = $user_id;
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_skills'] = explode(',', $user['skills']);

        // 2. PERSISTENT AUTH: Generate and store auth token
        $auth_token = bin2hex(random_bytes(32)); // Random 64-char token
        $token_hash = hash('sha256', $auth_token); // Hash it for security

        // Store hash in database and clear OTP
        $update = $conn->prepare("UPDATE users SET otp_code=NULL, otp_failed_attempts=0, auth_token=:token WHERE id=:id");
        $update->execute(['token' => $token_hash, 'id' => $user_id]);

        // Send raw token to browser as HTTP-only cookie (30 days)
        setcookie('auth_token', $auth_token, [
            'expires' => time() + (86400 * 30),
            'path' => '/',
            'domain' => '',
            'secure' => true,
            'httponly' => true,
            'samesite' => 'Lax'
        ]);

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
            header("Location: login.php?error=locked");
            exit();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Email - HackHub</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body>
    <div class="auth-container">
        <div class="auth-card glass-panel">
            <h1>📧 Email Verification</h1>
            <p>Enter the 6-digit code sent to <strong>
                    <?php echo htmlspecialchars($email); ?>
                </strong></p>

            <?php if ($error): ?>
            <div class="alert alert-danger">
                <?php echo $error; ?>
            </div>
            <?php
endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label>Verification Code</label>
                    <input type="text" name="otp" placeholder="Enter 6-digit code" required maxlength="6"
                        pattern="\d{6}" autofocus>
                </div>

                <button type="submit" name="verify" class="btn btn-primary btn-block">
                    ✅ Verify & Continue
                </button>
            </form>

            <p class="text-center mt-3">
                <a href="login.php">← Back to Login</a>
            </p>
        </div>
    </div>
</body>

</html>