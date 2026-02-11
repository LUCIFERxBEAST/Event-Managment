<?php
session_start();
include __DIR__ . '/../config/db.php';

// 1. Security & Input Check
if (!isset($_SESSION['user_id']) || !isset($_GET['event_id'])) {
    die("⛔ Access Denied.");
}

$user_id = $_SESSION['user_id'];
$event_id = intval($_GET['event_id']); // Sanitize input

// 2. Fetch User Status AND Event End Time
$sql = "SELECT r.status, u.name, h.title, h.event_end 
        FROM registrations r
        JOIN users u ON r.user_id = u.id
        JOIN hackathons h ON r.hackathon_id = h.id
        WHERE r.user_id = $user_id AND r.hackathon_id = $event_id";

$result = $conn->query($sql);
$data = $result->fetch();

// 3. CHECK 1: Did they attend?
if (!$data || $data['status'] != 'Present') {
    die("❌ Access Denied: You must be marked 'Present' by a guard to get a certificate.");
}

// 4. CHECK 2: Is the event over? (THE NEW FIX)
$current_time = time();
$event_end_time = strtotime($data['event_end']);

if ($current_time < $event_end_time) {
    $remaining = $event_end_time - $current_time;
    $hours = floor($remaining / 3600);
    die("⏳ Too Early! Certificates will be available after the event ends (in approx $hours hours).");
}

// 5. GENERATE IMAGE (Only runs if both checks pass)
// Ensure 'certificate_template.png' exists in your folder!
if (!file_exists('certificate_template.png')) {
    die("❌ Error: Certificate template not found on server.");
}

$image = imagecreatefrompng('certificate_template.png');

// Colors
$color_black = imagecolorallocate($image, 0, 0, 0);
$color_blue = imagecolorallocate($image, 20, 90, 200);

// Font Path (Fallback to built-in if ttf missing)
$font_path = __DIR__ . '/arial.ttf';

$name = strtoupper($data['name']);
$event = $data['title'];
$date = "Awarded on: " . date('F d, Y', $event_end_time);

if (file_exists($font_path)) {
    // High Quality Text (Size, Angle, X, Y, Color, Font, Text)
    // Adjust X/Y numbers to fit your specific PNG image
    imagettftext($image, 40, 0, 400, 500, $color_black, $font_path, $name);
    imagettftext($image, 20, 0, 400, 600, $color_blue, $font_path, $event);
    imagettftext($image, 15, 0, 400, 700, $color_black, $font_path, $date);
}
else {
    // Low Quality Fallback
    imagestring($image, 5, 300, 300, $name, $color_black);
    imagestring($image, 5, 300, 340, $event, $color_blue);
    imagestring($image, 4, 300, 380, $date, $color_black);
}

// Output
header('Content-Type: image/png');
header('Content-Disposition: attachment; filename="Certificate.png"');
imagepng($image);
imagedestroy($image);
?>