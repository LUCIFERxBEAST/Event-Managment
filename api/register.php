<?php
session_start();
include __DIR__ . '/../config/db.php';

if (isset($_POST['register'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    // REMOVED: $phone
    $pass = password_hash($_POST['password'], PASSWORD_DEFAULT);

    // Check if email exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = :email");
    $stmt->execute(['email' => $email]);
    if ($stmt->rowCount() > 0) {
        $error = "❌ This email is already registered!";
    }
    else {
        // GENERATE OTP
        $otp = rand(100000, 999999);

        // SAVE TO SESSION (No phone number here anymore)
        $_SESSION['temp_user'] = [
            'name' => $name,
            'email' => $email,
            'password' => $pass,
            'skills' => isset($_POST['skills']) ? implode(",", $_POST['skills']) : "",
            'otp' => $otp
        ];

        // SEND EMAIL
        include __DIR__ . '/../config/mail.php';
        if (sendOTP($email, $otp)) {
            header("Location: verify_email.php");
            exit();
        }
        else {
            $error = "Failed to send OTP. Check internet connection.";
        }
    }
}
?>


<?php $page_title = "Create Account | HackHub";
include __DIR__ . '/../includes/header.php';
?>

<div class="container fade-in" style="max-width: 550px; margin-top: 5rem;">
    <div class="glass-card">
        <h3 class="text-center mb-3 text-primary">🚀 Join HackHub</h3>

        <?php if (isset($error))
    echo "<div class='alert alert-danger'>$error</div>"; ?>

        <form method="POST">
            <div class="form-group">
                <label class="form-label">Full Name</label>
                <input type="text" name="name" class="form-control" placeholder="Name" required>
            </div>

            <div class="form-group">
                <label class="form-label">Email Address</label>
                <input type="email" name="email" class="form-control" placeholder="Email" required>
            </div>

            <div class="form-group">
                <label class="form-label mb-2">Your Interests / Skills</label><br>
                <input type="checkbox" name="skills[]" value="Web Dev" id="s1" class="skill-check"> <label for="s1"
                    class="skill-label">🌐 Web Dev</label>
                <input type="checkbox" name="skills[]" value="AI/ML" id="s2" class="skill-check"> <label for="s2"
                    class="skill-label">🤖 AI/ML</label>
                <input type="checkbox" name="skills[]" value="Design" id="s3" class="skill-check"> <label for="s3"
                    class="skill-label">🎨 Design</label>
                <input type="checkbox" name="skills[]" value="Cloud" id="s4" class="skill-check"> <label for="s4"
                    class="skill-label">☁️ Cloud</label>
                <input type="checkbox" name="skills[]" value="Mobile" id="s5" class="skill-check"> <label for="s5"
                    class="skill-label">📱 Mobile</label>
            </div>

            <div class="form-group">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control" placeholder="Enter Password" required>
            </div>
            <button type="submit" name="register" class="btn btn-primary" style="width: 100%; margin-bottom: 1rem;">
                Next: Verify Email ➝
            </button>
        </form>
        <p class="text-center mt-3" style="font-size: 0.9rem;">
            Already have an account? <a href="login.php"
                style="color: var(--primary-color); font-weight: 600;">Login</a>
        </p>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>