<?php

// 1. GMAIL SMTP SETTINGS
// Use Environment Variables for security (compatible with Vercel & .env)
$smtp_host = getenv('SMTP_HOST');
$smtp_user = getenv('SMTP_USER'); // Default or from Env
$smtp_pass = getenv('SMTP_PASS');       // Default or from Env
$smtp_port = getenv('SMTP_PORT');

define('SMTP_HOST', $smtp_host);
define('SMTP_USER', $smtp_user);
define('SMTP_PASS', $smtp_pass);
define('SMTP_PORT', $smtp_port);

// 2. APP SETTINGS
define('FROM_NAME', 'Hackathon Security Team');
?>