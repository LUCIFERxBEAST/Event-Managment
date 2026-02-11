<?php
session_start();
include __DIR__ . '/../config/db.php';

// Security: If no email param, go back to register
if (!isset($_GET['email'])) {
    header("Location: register.php");
    exit();
}

$email = $_GET['email'];
$error = "";

// Fetch User Data to display name/email
$stmt = $conn->prepare("SELECT * FROM users WHERE email = :email");
$stmt->execute(['email' => $email]);
$userData = $stmt->fetch();

if (!$userData) {
    die("❌ Error user not found. Please register again.");
}

if (isset($_POST['verify'])) {
    $entered_otp = trim($_POST['otp']);

    // Check DB OTP
    if ($entered_otp == $userData['otp_code']) {

        // CHECK EXPIRY
        if (strtotime($userData['otp_expiry']) < time()) {
            $error = "❌ OTP Expired! Please login to request a new one.";
        }
        else {
            // ✅ MATCHED!
            $_SESSION['user_id'] = $userData['id'];
            $_SESSION['user_name'] = $userData['name'];
            $_SESSION['user_skills'] = explode(',', $userData['skills']);

            // CLEAR OTP
            $clear = $conn->prepare("UPDATE users SET otp_code = NULL, otp_expiry = NULL WHERE id = :id");
            $clear->execute(['id' => $userData['id']]);

            echo "<script>alert('✅ Verification Successful!'); window.location.href = 'dashboard.php';</script>";
            exit();
        }
    }
    else {
        $error = "❌ Invalid OTP! Please try again.";
    // Optional: Increment failed attempts here
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Email | HackHub</title>
    <!-- Google Fonts: Outfit -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body {
            display: flex;
            align-items: center;
            justify-content: center;
        }
    </style>
</head>

<body>

    <div class="container fade-in" style="max-width: 450px;">
        <div class="glass-card text-center" style="padding: 3rem 2rem;">

            <div style="font-size: 3rem; margin-bottom: 1rem;">📧</div>
            <h2 style="font-weight: 700; margin-bottom: 1rem;">Check Your Email</h2>
            <p style="color: #666; margin-bottom: 2rem;">
                We sent a 6-digit code to <br>
                <strong style="color: var(--primary-color);">
                    <?php echo htmlspecialchars($userData['email']); ?>
                </strong>
            </p>

            <?php if ($error)
    echo "<div class='alert alert-danger'>$error</div>"; ?>

            <form method="POST">
                <div class="form-group">
                    <label class="form-label" style="text-align: left; width: 100%;">Enter Verification Code</label>
                    <input type="number" name="otp" class="form-control"
                        style="font-size: 2rem; letter-spacing: 10px; text-align: center; height: 60px; font-weight: 700; color: var(--primary-color);"
                        placeholder="000000" required>
                </div>
                <button type="submit" name="verify" class="btn btn-primary" style="width: 100%; padding: 1rem;">Verify &
                    Login</button>
            </form>

            <div style="margin-top: 2rem; border-top: 1px solid rgba(0,0,0,0.1); padding-top: 1rem;">
                <a href="register.php" style="color: #888; font-size: 0.9rem; text-decoration: none;">Wrong email?
                    Restart Registration</a>
            </div>
        </div>
    </div>

</body>

</html>