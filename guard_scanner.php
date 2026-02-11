<?php
session_start();
// Security: Kick out if not logged in
if (!isset($_SESSION['guard_id']) || !isset($_SESSION['event_id'])) {
    header("Location: guard_login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Scanner: <?php echo htmlspecialchars($_SESSION['event_title']); ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #000; color: white; }
        #reader { width: 100%; border-radius: 10px; border: 4px solid #444; }
        .status-box { padding: 15px; border-radius: 10px; margin-top: 20px; display: none; text-align: center; }
        .success { background-color: #198754; color: white; }
        .error { background-color: #dc3545; color: white; }
    </style>
</head>
<body class="p-3">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <span class="badge bg-warning text-dark">ON DUTY</span>
            <h5 class="mt-1 mb-0"><?php echo htmlspecialchars($_SESSION['event_title']); ?></h5>
        </div>
        <a href="logout.php" class="btn btn-sm btn-outline-light">Logout</a>
    </div>

    <div id="reader"></div>

    <div id="result-box" class="status-box"></div>

    <div class="text-center mt-4">
        <p class="text-muted small">Scanner Active. Point at QR Code.</p>
    </div>

    <script>
    let isScanning = true;

    function onScanSuccess(decodedText, decodedResult) {
        if (!isScanning) return;
        isScanning = false; // Pause scanner

        let box = document.getElementById("result-box");
        box.style.display = "block";
        box.className = "status-box bg-light text-dark";
        box.innerHTML = "⏳ Verifying...";

        // SEND DATA TO API
        fetch('api/process_scan.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ 
                hash: decodedText,
                event_id: <?php echo $_SESSION['event_id']; ?> 
            })
        })
        .then(response => response.text()) 
        .then(text => {
            try {
                let data = JSON.parse(text); 

                if (data.status === 'success') {
                    // ✅ SUCCESS (Visual Only)
                    box.className = "status-box success";
                    box.innerHTML = `<h1>✅ GRANTED</h1><h3>${data.name}</h3><p>Marked Present</p>`;
                } else {
                    // ⛔ DENIED (Visual Only)
                    box.className = "status-box error";
                    box.innerHTML = `<h1>⛔ DENIED</h1><h3>${data.message}</h3>`;
                }
            } catch (e) {
                console.error("Server Error:", text);
                box.className = "status-box error";
                box.innerHTML = "⚠️ <b>Server Error</b><br>Check console for details.";
            }
        })
        .catch(err => {
            box.className = "status-box error";
            box.innerHTML = "📶 <b>Network Error</b><br>Check internet connection.";
        })
        .finally(() => {
            // Restart scanner after 3 seconds
            setTimeout(() => { 
                isScanning = true; 
                box.style.display = "none"; 
            }, 3000);
        });
    }

    let html5QrcodeScanner = new Html5QrcodeScanner("reader", { fps: 10, qrbox: 250 });
    html5QrcodeScanner.render(onScanSuccess);
</script>

</body>
</html>