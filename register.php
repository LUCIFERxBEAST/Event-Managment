<?php
session_start();
include 'config/db.php';

if (isset($_POST['register'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    // REMOVED: $phone
    $pass = password_hash($_POST['password'], PASSWORD_DEFAULT);

    // Check if email exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);

    if ($stmt->fetch()) {
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
        include 'config/mail.php';
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

<!DOCTYPE html>
<html>

<head>
    <title>Create Account</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .skill-check {
            display: none;
        }

        .skill-label {
            cursor: pointer;
            padding: 8px 15px;
            border: 1px solid #0d6efd;
            border-radius: 20px;
            display: inline-block;
            margin: 2px;
            color: #0d6efd;
        }

        .skill-check:checked+.skill-label {
            background-color: #0d6efd;
            color: white;
        }
    </style>
</head>

<body class="bg-light d-flex align-items-center justify-content-center" style="min-height: 100vh;">

    <div class="card shadow p-4 m-3" style="max-width: 500px; width: 100%;">
        <h3 class="text-center mb-3">🚀 Hackathon Hub</h3>

        <?php if (isset($error))
    echo "<div class='alert alert-danger'>$error</div>"; ?>

        <form method="POST">
            <div class="mb-3">
                <label>Name</label>
                <input type="text" name="name" class="form-control" required>
            </div>

            <div class="mb-3">
                <label>Email</label>
                <input type="email" name="email" class="form-control" required>
            </div>

            <div class="mb-3">
                <label class="fw-bold mb-2">Interests</label><br>
                <input type="checkbox" name="skills[]" value="Web Dev" id="s1" class="skill-check"> <label for="s1"
                    class="skill-label">🌐 Web Dev</label>
                <input type="checkbox" name="skills[]" value="AI/ML" id="s2" class="skill-check"> <label for="s2"
                    class="skill-label">🤖 AI/ML</label>
                <input type="checkbox" name="skills[]" value="Design" id="s3" class="skill-check"> <label for="s3"
                    class="skill-label">🎨 Design</label>
            </div>

            <div class="mb-3">
                <label>Password</label>
                <input type="password" name="password" class="form-control" required>
            </div>
            <button type="submit" name="register" class="btn btn-primary w-100 btn-lg">Next: Verify Email ➝</button>
        </form>
        <p class="text-center mt-3">Already have an account? <a href="login.php">Login</a></p>
    </div>

</body>

</html>