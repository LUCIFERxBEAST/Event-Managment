<?php
session_start();
include __DIR__ . '/../config/db.php';
include __DIR__ . '/../config/mail.php'; // 👈 Use the same mail config as register.php

$error = "";

if (isset($_POST['login'])) {
    $email = trim($_POST['email']);
    $pass = $_POST['password'];

    // 1. Get user from DB
    // Use named placeholders or ? with execute array
    $stmt = $pdo->prepare("SELECT id, name, password, skills FROM users WHERE email = ?");
    $stmt->execute([$email]);

    if ($row = $stmt->fetch()) {
        // 2. Verify Password
        if (password_verify($pass, $row['password'])) {

            // 3. Generate OTP
            $otp = rand(100000, 999999);

            // 4. Store in TEMP session (Similar to your registration logic)
            $_SESSION['temp_login'] = [
                'id' => $row['id'],
                'name' => $row['name'],
                'email' => $email,
                'skills' => $row['skills'],
                'otp' => $otp
            ];

            // 5. Use your WORKING sendOTP function
            if (sendOTP($email, $otp)) {
                header("Location: login_verify.php");
                exit();
            }
            else {
                $error = "❌ Failed to send OTP. Please check your mail settings.";
            }

        }
        else {
            $error = "❌ Incorrect Password!";
        }
    }
    else {
        $error = "❌ Email not found!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <title>Login | Secure Access</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light d-flex align-items-center justify-content-center" style="height: 100vh;">
    <div class="card shadow p-4" style="max-width: 400px; width: 100%;">
        <h3 class="text-center fw-bold mb-4">🔐 Secure Login</h3>
        <?php if ($error)
    echo "<div class='alert alert-danger'>$error</div>"; ?>
        <form method="POST">
            <div class="mb-3">
                <label>Email Address</label>
                <input type="email" name="email" class="form-control" required>
            </div>
            <div class="mb-3">
                <label>Password</label>
                <input type="password" name="password" class="form-control" required>
            </div>
            <button type="submit" name="login" class="btn btn-primary w-100 fw-bold">Next: Verify Email ➝</button>
        </form>
        <p class="text-center mt-3 small">New user? <a href="register.php">Create Account</a></p>
    </div>
</body>

</html>