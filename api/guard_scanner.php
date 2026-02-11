<?php
session_start();
if (!isset($_GET['id']))
    die("Event ID missing.");
$event_id = $_GET['id'];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scanner | Guard Link</title>
    <!-- Google Fonts: Share Tech Mono for HUD effect -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Share+Tech+Mono&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>
    <style>
        body {
            background: #000;
            color: #00ff88;
            font-family: 'Share Tech Mono', monospace;
            margin: 0;
            overflow: hidden;
            /* Prevent scrolling on mobile scanner */
        }

        .hud-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100vh;
            position: relative;
            background: radial-gradient(circle at center, rgba(0, 50, 20, 0.4), #000);
        }

        .scanner-frame {
            width: 80%;
            max-width: 400px;
            aspect-ratio: 1;
            border: 2px solid rgba(0, 255, 136, 0.3);
            position: relative;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 0 20px rgba(0, 255, 136, 0.2);
        }

        /* Corner Accents */
        .scanner-frame::before,
        .scanner-frame::after,
        .scanner-corners::before,
        .scanner-corners::after {
            content: "";
            position: absolute;
            width: 40px;
            height: 40px;
            border: 4px solid #00ff88;
            pointer-events: none;
            z-index: 10;
            box-shadow: 0 0 10px #00ff88;
        }

        .scanner-frame::before {
            top: 0;
            left: 0;
            border-right: none;
            border-bottom: none;
            border-radius: 16px 0 0 0;
        }

        .scanner-frame::after {
            top: 0;
            right: 0;
            border-left: none;
            border-bottom: none;
            border-radius: 0 16px 0 0;
        }

        .scanner-corners {
            position: absolute;
            inset: 0;
            pointer-events: none;
        }

        .scanner-corners::before {
            bottom: 0;
            left: 0;
            border-right: none;
            border-top: none;
            border-radius: 0 0 0 16px;
            border: 4px solid #00ff88;
            border-right: none;
            border-top: none;
        }

        .scanner-corners::after {
            bottom: 0;
            right: 0;
            border-left: none;
            border-top: none;
            border-radius: 0 0 16px 0;
            border: 4px solid #00ff88;
            border-left: none;
            border-top: none;
        }

        #reader {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .result-overlay {
            display: none;
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 90%;
            max-width: 350px;
            padding: 30px;
            text-align: center;
            border-radius: 12px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(15px);
            z-index: 100;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.8);
        }

        .success-mode {
            background: rgba(0, 255, 136, 0.2);
            border-color: #00ff88;
            color: #fff;
        }

        .warning-mode {
            background: rgba(255, 193, 7, 0.2);
            border-color: #ffc107;
            color: #fff;
        }

        .fail-mode {
            background: rgba(255, 71, 87, 0.2);
            border-color: #ff4757;
            color: #ff4757;
        }

        .btn-action {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid currentColor;
            color: inherit;
            padding: 10px 20px;
            margin-top: 15px;
            font-family: inherit;
            text-transform: uppercase;
            letter-spacing: 2px;
            cursor: pointer;
            width: 100%;
            transition: all 0.2s;
        }

        .btn-action:hover {
            background: rgba(255, 255, 255, 0.2);
        }
    </style>
</head>

<body>

    <div class="hud-container">
        <h3 style="margin-bottom: 20px; text-transform: uppercase; letter-spacing: 2px;">
            <span style="color: #fff;">System</span><span style="color: #00ff88;">Guard</span> v2.0
        </h3>

        <div class="scanner-frame">
            <div class="scanner-corners"></div>
            <div id="reader"></div>
        </div>

        <p style="margin-top: 20px; opacity: 0.7; font-size: 0.8rem;">ALIGN QR CODE WITHIN FRAME</p>
        <a href="dashboard.php" style="color: #666; font-size: 0.8rem; text-decoration: none; margin-top: 10px;">[
            TERMINATE SESSION ]</a>
    </div>

    <!-- RESULT POPUP -->
    <div id="resultCard" class="result-overlay">
        <h1 id="resIcon" style="font-size: 4rem; margin: 0 0 10px 0;"></h1>
        <h2 id="resTitle" style="margin: 0; font-size: 1.5rem; text-transform: uppercase;"></h2>
        <p id="resUser" style="margin: 10px 0; font-size: 1.2rem; font-weight: bold;"></p>
        <button onclick="resumeScanning()" class="btn-action">SCAN NEXT TARGET</button>
    </div>

    <audio id="beep-success" src="https://www.soundjay.com/buttons/sounds/button-3.mp3"></audio>
    <audio id="beep-fail" src="https://www.soundjay.com/buttons/sounds/button-10.mp3"></audio>

    <script>
        let html5QrcodeScanner;
        const eventId = <? php echo $event_id; ?>;
        let isScanning = true;

        function onScanSuccess(decodedText, decodedResult) {
            if (!isScanning) return;
            isScanning = false;

            // 1. Send to Backend
            fetch('api/scan_verify.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ qr_hash: decodedText, event_id: eventId })
            })
                .then(response => response.json())
                .then(data => {
                    showResult(data);
                })
                .catch(err => {
                    console.error(err);
                    alert("UPLINK FAILED: CHECK NETWORK");
                    resumeScanning();
                });
        }

        function showResult(data) {
            const card = document.getElementById('resultCard');
            const icon = document.getElementById('resIcon');
            const title = document.getElementById('resTitle');
            const user = document.getElementById('resUser');

            card.style.display = 'block';
            card.className = 'result-overlay'; // Reset

            if (data.status === 'success') {
                card.classList.add('success-mode');
                icon.innerText = '✅';
                document.getElementById('beep-success').play();
            } else if (data.status === 'warning') {
                card.classList.add('warning-mode');
                icon.innerText = '⚠️';
                document.getElementById('beep-fail').play();
            } else {
                card.classList.add('fail-mode');
                icon.innerText = '⛔';
                document.getElementById('beep-fail').play();
            }

            title.innerText = data.message;
            user.innerText = data.user;

            // Pause Camera
            html5QrcodeScanner.pause();
        }

        function resumeScanning() {
            document.getElementById('resultCard').style.display = 'none';
            isScanning = true;
            html5QrcodeScanner.resume();
        }

        // INITIALIZE SCANNER (Using standard config)
        html5QrcodeScanner = new Html5QrcodeScanner("reader", { fps: 10, qrbox: 250 });
        html5QrcodeScanner.render(onScanSuccess);

    </script>

</body>

</html>