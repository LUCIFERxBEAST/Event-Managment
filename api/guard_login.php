<?php
session_start();
include __DIR__ . '/../config/db.php';

$error = "";

if (isset($_POST['token_login'])) {
    $token = trim($_POST['token']);

    // Check if Token exists in Staff Table
    $stmt = $conn->prepare("SELECT * FROM event_staff WHERE access_token = :token");
    $stmt->execute(['token' => $token]);

    if ($row = $stmt->fetch()) {
        // ✅ Valid Token
        $_SESSION['staff_logged_in'] = true;
        $_SESSION['staff_id'] = $row['id'];
        $_SESSION['staff_role'] = $row['role'];
        $_SESSION['event_id'] = $row['hackathon_id'];

        // ROUTING: Send them to the right page based on ROLE
        if ($row['role'] == 'Guard') {
            header("Location: guard_scanner.php?id=" . $row['hackathon_id']);
        }
        else {
            // Support Staff interface is disabled
            header("Location: index.php");
        }
        exit();
    }
    else {
        $error = "⛔ Invalid Token!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Access | System Core</title>
    <!-- Google Fonts: Outfit (and Monospace for code effect) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link
        href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&family=Share+Tech+Mono&display=swap"
        rel="stylesheet">
    <style>
        body {
            margin: 0;
            padding: 0;
            background-color: #050505;
            background-image:
                linear-gradient(rgba(0, 255, 0, 0.03) 1px, transparent 1px),
                linear-gradient(90deg, rgba(0, 255, 0, 0.03) 1px, transparent 1px);
            background-size: 20px 20px;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Outfit', sans-serif;
            color: #fff;
            overflow: hidden;
        }

        .login-box {
            background: rgba(10, 20, 30, 0.8);
            border: 1px solid #333;
            padding: 3rem;
            border-radius: 12px;
            /* Smooth corners */
            width: 100%;
            max-width: 420px;
            text-align: center;
            position: relative;
            box-shadow: 0 0 40px rgba(0, 255, 136, 0.1);
            backdrop-filter: blur(10px);
        }

        .login-box::before {
            content: '';
            position: absolute;
            top: -2px;
            left: -2px;
            right: -2px;
            bottom: -2px;
            background: linear-gradient(45deg, #00ff88, #00d2d3, transparent, transparent);
            z-index: -1;
            border-radius: 14px;
            opacity: 0.5;
        }

        h1 {
            font-size: 3rem;
            margin-bottom: 0.5rem;
        }

        h3 {
            font-family: 'Share Tech Mono', monospace;
            letter-spacing: 3px;
            color: #00ff88;
            margin-bottom: 2rem;
            text-transform: uppercase;
        }

        .token-input {
            background: rgba(0, 0, 0, 0.5);
            border: 2px solid #333;
            color: #00ff88;
            font-family: 'Share Tech Mono', monospace;
            font-size: 1.5rem;
            text-align: center;
            letter-spacing: 4px;
            text-transform: uppercase;
            width: 100%;
            padding: 1rem;
            border-radius: 8px;
            transition: all 0.3s;
            box-sizing: border-box;
            /* Ensure padding doesn't break width */
        }

        .token-input:focus {
            outline: none;
            border-color: #00ff88;
            box-shadow: 0 0 15px rgba(0, 255, 136, 0.3);
        }

        .btn-auth {
            background: #00ff88;
            color: #000;
            font-weight: 700;
            font-family: 'Share Tech Mono', monospace;
            border: none;
            padding: 1rem;
            width: 100%;
            border-radius: 8px;
            font-size: 1.2rem;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 1.5rem;
            letter-spacing: 1px;
        }

        .btn-auth:hover {
            background: #00d2d3;
            box-shadow: 0 0 20px rgba(0, 210, 211, 0.5);
        }

        .error-msg {
            color: #ff4757;
            font-family: 'Share Tech Mono', monospace;
            margin-bottom: 1rem;
        }

        /* Decorative Scan Line */
        .scan-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(to bottom, rgba(0, 255, 136, 0), rgba(0, 255, 136, 0.1) 50%, rgba(0, 255, 136, 0));
            background-size: 100% 4px;
            animation: scan 10s linear infinite;
            pointer-events: none;
            z-index: 10;
        }

        @keyframes scan {
            0% {
                transform: translateY(-100%);
            }

            100% {
                transform: translateY(100%);
            }
        }
    </style>
</head>

<body>

    <div class="scan-overlay"></div>

    <div class="login-box">
        <h1>🛡️</h1>
        <h3>Security Clearance</h3>

        <?php if ($error)
    echo "<div class='error-msg'>$error</div>"; ?>

        <form method="POST">
            <label
                style="color: #666; font-size: 0.8rem; letter-spacing: 1px; margin-bottom: 10px; display: block;">ENTER
                ACCESS TOKEN</label>
            <input type="text" name="token" class="token-input" placeholder="XXXX-XXXX" required autocomplete="off">
            <button type="submit" name="token_login" class="btn-auth">AUTHENTICATE</button>
        </form>
    </div>

</body>

</html>