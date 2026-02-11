<?php
// config/mail.php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Load PHPMailer
require __DIR__ . '/../PHPMailer/src/Exception.php';
require __DIR__ . '/../PHPMailer/src/PHPMailer.php';
require __DIR__ . '/../PHPMailer/src/SMTP.php';

// Load Credentials
require_once __DIR__ . '/credentials.php';

function sendOTP($toEmail, $otpCode) {
    $mail = new PHPMailer(true);

    try {
        // 1. SERVER SETTINGS (Uses constants from credentials.php)
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USER;
        $mail->Password   = SMTP_PASS;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = SMTP_PORT;

        // 2. SENDER & RECIPIENT
        $mail->setFrom(SMTP_USER, FROM_NAME);
        $mail->addAddress($toEmail);

        // 3. CONTENT
        $mail->isHTML(true);
        $mail->Subject = '🔐 Your Login Verification Code';
        $mail->Body    = "
            <div style='font-family: sans-serif; background: #f4f4f4; padding: 20px;'>
                <div style='background: white; padding: 20px; border-radius: 5px; text-align: center;'>
                    <h2>Verification Required</h2>
                    <p>Your Secure OTP is:</p>
                    <h1 style='color: #0d6efd; letter-spacing: 5px; font-size: 32px;'>$otpCode</h1>
                    <p style='color: #999; font-size: 12px;'>Valid for 10 minutes.</p>
                </div>
            </div>";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Mail Error: {$mail->ErrorInfo}");
        return false;
    }
}
?>