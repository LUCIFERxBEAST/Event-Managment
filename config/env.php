<?php
/* FILE: config/env.php - Environment Variable Loader */

/**
 * Load environment variables from .env file
 * 
 * @param string $path Path to .env file
 * @return void
 */
function loadEnv($path)
{
    if (!file_exists($path)) {
        die("Error: .env file not found at $path. Please create it from .env.example");
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        // Skip comments
        if (strpos(trim($line), '#') === 0) {
            continue;
        }

        // Parse KEY=VALUE
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);

            // Set environment variable if not already set
            if (!getenv($key)) {
                putenv("$key=$value");
                $_ENV[$key] = $value;
                $_SERVER[$key] = $value;
            }
        }
    }
}

// Auto-load .env file when this file is included
$envPath = __DIR__ . '/../.env';
loadEnv($envPath);
?>