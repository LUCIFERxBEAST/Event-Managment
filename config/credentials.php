<?php
/* FILE: config/credentials.php - SMTP Settings from Environment */

// Load environment variables
require_once __DIR__ . '/env.php';

// SMTP SETTINGS from environment
define('SMTP_HOST', getenv('SMTP_HOST') ?: 'smtp.gmail.com');
define('SMTP_USER', getenv('SMTP_USER') ?: '');
define('SMTP_PASS', getenv('SMTP_PASS') ?: '');
define('SMTP_PORT', getenv('SMTP_PORT') ?: 587);

// APP SETTINGS
define('FROM_NAME', getenv('FROM_NAME') ?: 'Hackathon Security Team');
?>