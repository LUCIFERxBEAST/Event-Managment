<?php
// --- 🚨 PREVENT 500 ERRORS ---
mysqli_report(MYSQLI_REPORT_OFF);
ini_set('display_errors', 1);
error_reporting(E_ALL);
// -----------------------------

session_start();
include 'config/db.php';

// 1. Security Check
if (!isset($_SESSION['user_id']) || !isset($_GET['event_id'])) {
    die("⛔ Error: Missing User or Event ID. <a href='dashboard.php'>Back to Dashboard</a>");
}

$user_id = $_SESSION['user_id'];
$event_id = intval($_GET['event_id']);

// 2. Fetch Data (Fixed Column Name: event_start)
$sql = "SELECT u.name, h.title, h.event_start 
        FROM registrations r 
        JOIN users u ON r.user_id = u.id 
        JOIN hackathons h ON r.hackathon_id = h.id 
        WHERE r.user_id = ? 
        AND r.hackathon_id = ?";

$stmt = $pdo->prepare($sql);
$stmt->execute([$user_id, $event_id]);
$data = $stmt->fetch();

if (!$data) {
    die("<h2 style='color:red; text-align:center; margin-top:50px;'>⛔ Access Denied</h2>
         <p style='text-align:center;'>You are not registered for this event.</p>");
}

// 3. Status Check (Optional: Remove if you want to test without being marked 'Present')
// To force 'Present' check, uncomment the lines below:
/*
$status_check = $conn->query("SELECT status FROM registrations WHERE user_id=$user_id AND hackathon_id=$event_id");
$status_row = $status_check->fetch_assoc();
if ($status_row['status'] != 'Present') {
     die("<h2 style='color:red; text-align:center; margin-top:50px;'>⏳ Certificate Locked</h2>
          <p style='text-align:center;'>You must attend the event and be scanned in by a Guard to download this.</p>");
}
*/

$participant_name = strtoupper($data['name']);
$event_name = $data['title'];
// Fix: Use 'event_start' instead of 'event_date'
$date = date("F j, Y", strtotime($data['event_start']));
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <title>Certificate -
        <?php echo htmlspecialchars($participant_name); ?>
    </title>
    <link
        href="https://fonts.googleapis.com/css2?family=Pinyon+Script&family=Cinzel:wght@400;700&family=Open+Sans:wght@400;600&display=swap"
        rel="stylesheet">
    <style>
        body {
            background-color: #f0f0f0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            font-family: 'Open Sans', sans-serif;
        }

        .cert-container {
            width: 900px;
            height: 600px;
            background-color: white;
            padding: 20px;
            position: relative;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.2);
            text-align: center;
            border: 10px solid #2c3e50;
        }

        .border-inner {
            border: 5px solid #d4af37;
            /* Gold Color */
            height: 100%;
            width: 100%;
            box-sizing: border-box;
            padding: 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .title {
            font-family: 'Cinzel', serif;
            font-size: 50px;
            color: #2c3e50;
            font-weight: 700;
            margin-bottom: 10px;
            text-transform: uppercase;
        }

        .subtitle {
            font-size: 18px;
            color: #7f8c8d;
            margin-bottom: 30px;
        }

        .name {
            font-family: 'Pinyon Script', cursive;
            font-size: 70px;
            color: #d4af37;
            margin: 10px 0;
            border-bottom: 2px solid #ecf0f1;
            display: inline-block;
            padding: 0 40px;
            min-width: 400px;
        }

        .description {
            font-size: 18px;
            color: #555;
            margin-top: 30px;
            line-height: 1.6;
        }

        .event-name {
            font-weight: bold;
            color: #2c3e50;
            font-size: 22px;
        }

        .footer {
            margin-top: 60px;
            display: flex;
            justify-content: space-around;
        }

        .signature {
            border-top: 2px solid #333;
            width: 200px;
            padding-top: 10px;
            font-family: 'Cinzel', serif;
        }

        .no-print {
            position: fixed;
            top: 20px;
            right: 20px;
            background: white;
            padding: 10px;
            border-radius: 5px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        button {
            background: #d4af37;
            color: white;
            border: none;
            padding: 10px 20px;
            font-size: 16px;
            cursor: pointer;
            border-radius: 5px;
            font-weight: bold;
        }

        button:hover {
            background: #b3922b;
        }

        @media print {
            .no-print {
                display: none;
            }

            body {
                background: white;
                margin: 0;
            }

            .cert-container {
                box-shadow: none;
                border: 5px solid #2c3e50;
                width: 100%;
                height: 100vh;
            }
        }
    </style>
</head>

<body>

    <div class="no-print">
        <button onclick="window.print()">🖨️ Print / Save as PDF</button>
        <br><br>
        <a href="dashboard.php" style="text-decoration:none; color:#555;">← Back to Dashboard</a>
    </div>

    <div class="cert-container">
        <div class="border-inner">
            <div class="title">Certificate of Participation</div>
            <div class="subtitle">This certificate is proudly presented to</div>

            <div class="name">
                <?php echo htmlspecialchars($participant_name); ?>
            </div>

            <div class="description">
                For active participation and excellence demonstrated in the<br>
                <span class="event-name">
                    <?php echo htmlspecialchars($event_name); ?>
                </span>
            </div>

            <div class="footer">
                <div class="signature">
                    <?php echo $date; ?><br>
                    <small style="font-size:12px; color:#777;">DATE</small>
                </div>
                <div class="signature">
                    HackHub Org.<br>
                    <small style="font-size:12px; color:#777;">ORGANIZER</small>
                </div>
            </div>
        </div>
    </div>

</body>

</html>