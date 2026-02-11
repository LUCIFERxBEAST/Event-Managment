<?php
session_start();
include '../config/db.php';

$error = "";

if (isset($_POST['login'])) {
    $email = trim($_POST['email']);
    $pass = $_POST['password'];

    $stmt = $conn->prepare("SELECT id, name, password FROM users WHERE email = :email");
    $stmt->execute(['email' => $email]);

    if ($row = $stmt->fetch()) {
        if (password_verify($pass, $row['password'])) {
            // 1. GENERATE SECURE OTP
            $otp = rand(100000, 999999);

            // 2. SET EXPIRY (Current Time + 10 Minutes)
            $expiry = date("Y-m-d H:i:s", strtotime("+10 minutes"));

            // 3. UPDATE DATABASE (Reset attempts to 0)
            $update = $conn->prepare("UPDATE users SET otp_code=:otp, otp_expiry=:expiry, otp_failed_attempts=0 WHERE id=:id");
            $update->execute(['otp' => $otp, 'expiry' => $expiry, 'id' => $row['id']]);

            // 4. SAVE ID TO SESSION (For the next step)
            $_SESSION['temp_login_id'] = $row['id'];
            $_SESSION['temp_email'] = $email; // Used for "Simulated Email"

            // 5. REDIRECT to Verification
            // Note: passing OTP in URL purely for Localhost testing. Remove in production!
            include '../config/mail.php';
            sendOTP($email, $otp); // Send real email
            header("Location: login_verify.php"); // Redirect securely
            exit();
        }
        else {
            $error = "❌ Wrong Password!";
        }
    }
    else {
        $error = "❌ Email not found!";
    }
}
?>


<?php $page_title = "Login | Secure Access";
include '../includes/header.php';
?>

<div class="container flex-center fade-in" style="min-height: 80vh;">
    <div class="glass-card" style="max-width: 450px; width: 100%;">
        <div class="text-center mb-4">
            <h1 class="text-title">🔐</h1>
            <h3 class="text-primary">Secure Login</h3>
        </div>

        <?php if ($error): ?>
        <div class='alert alert-danger'>
            <?php echo $error; ?>
        </div>
        <?php
endif; ?>

        <form method="POST">
            <div class="form-group">
                <label class="form-label">Email Address</label>
                <input type="email" name="email" class="form-control" placeholder="Email" required>
            </div>
            <div class="form-group">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control" placeholder="Enter Password" required>
            </div>
            <button type="submit" name="login" class="btn btn-primary" style="width: 100%; margin-top: 1rem;">
                Next: Send OTP ➝
            </button>
        </form>
        <p class="text-center mt-4" style="font-size: 0.95rem;">
            New here? <a href="register.php" style="color: var(--primary-color); font-weight: 600;">Create Account</a>
        </p>
    </div>
</div>

<?php include '../includes/footer.php'; ?>