<?php
session_start();
include '../config/db.php';

// Security: Kick back if no login attempt is active
if (!isset($_SESSION['temp_login'])) {
    header("Location: login.php");
    exit();
}

$userData = $_SESSION['temp_login'];
$error = "";

if (isset($_POST['verify'])) {
    $entered_otp = $_POST['otp'];

    if ($entered_otp == $userData['otp']) {
        // ✅ SUCCESS!
        $_SESSION['user_id'] = $userData['id'];
        $_SESSION['user_name'] = $userData['name'];
        $_SESSION['user_skills'] = $userData['skills'];

        unset($_SESSION['temp_login']); // Clear temp data

        echo "<script>alert('✅ Login Successful!'); window.location.href='dashboard.php';</script>";
        exit();
    } else {
        $error = "❌ Invalid OTP! Please try again.";
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
    <div class="text-center">
        <h2 class="fw-bold mb-3">🛡️ Two-Step Verification</h2>
        <p class="text-muted">Enter the code sent to <strong><?php echo htmlspecialchars($userData['email']); ?></strong></p>
        
        <?php if($error) echo "<div class='alert alert-danger'>$error</div>"; ?>

        <form method="POST" class="d-inline-block text-start" style="max-width: 300px;">
            <div class="mb-3">
                <input type="number" name="otp" class="form-control text-center text-primary fw-bold" style="font-size: 28px; letter-spacing: 5px;" placeholder="000000" required autofocus>
            </div>
            <button type="submit" name="verify" class="btn btn-success w-100 fw-bold py-2">Verify & Login</button>
        </form>
        <br><br>
        <a href="login.php" class="text-muted small">Wrong email? Back to Login</a>
    </div>
</body>
</html>