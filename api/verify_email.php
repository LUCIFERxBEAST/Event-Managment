<?php
session_start();
include '../config/db.php';

// Security: If no temp user exists, go back to register
if (!isset($_SESSION['temp_user'])) {
    header("Location: register.php");
    exit();
}

$userData = $_SESSION['temp_user'];
$error = "";

if (isset($_POST['verify'])) {
    $entered_otp = $_POST['otp'];

    if ($entered_otp == $userData['otp']) {
        // ✅ MATCHED!
        // REMOVED 'mobile' from this query
        $stmt = $pdo->prepare("INSERT INTO users (name, email, password, skills) VALUES (?, ?, ?, ?)");

        if ($stmt->execute([$userData['name'], $userData['email'], $userData['password'], $userData['skills']])) {
            $_SESSION['user_id'] = $pdo->lastInsertId();
            $_SESSION['user_name'] = $userData['name'];
            $_SESSION['user_skills'] = $userData['skills'];

            unset($_SESSION['temp_user']);

            echo "<script>alert('✅ Verification Successful!'); window.location.href = 'dashboard.php';</script>";
            exit();
        }
        else {
            $error = "Database Error: " . implode(" ", $stmt->errorInfo());
        }
    }
    else {
        $error = "❌ Invalid OTP! Please try again.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <title>Verify Email</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-white d-flex align-items-center justify-content-center" style="height: 100vh;">

    <div class="text-center">
        <h2 class="fw-bold mb-3">📧 Check Your Email</h2>
        <p class="text-muted">We sent a 6-digit code to <strong>
                <?php echo htmlspecialchars($userData['email']); ?>
            </strong></p>

        <?php if ($error)
    echo "<div class='alert alert-danger'>$error</div>"; ?>

        <form method="POST" class="d-inline-block text-start" style="max-width: 300px;">
            <div class="mb-3">
                <label class="fw-bold">Enter Verification Code</label>
                <input type="number" name="otp" class="form-control text-center text-primary fw-bold"
                    style="font-size: 24px; letter-spacing: 5px;" placeholder="000000" required>
            </div>
            <button type="submit" name="verify" class="btn btn-primary w-100">Verify & Login</button>
        </form>

        <br><br>
        <a href="register.php" class="text-muted small">Wrong email? Restart Registration</a>
    </div>

</body>

</html>