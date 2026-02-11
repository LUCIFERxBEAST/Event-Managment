<?php
/* FILE: config/env.php - Environment Variable Loader (Local + Vercel Compatible) */

/**
 * Load environment variables from .env file (local development)
 * On Vercel/production, environment variables are already loaded by the platform
 * 
 * @param string $path Path to .env file
 * @return void
 */
function loadEnv($path)
{
    // Check if environment variables are already set (Vercel, production environments)
    // If DB_HOST is already set, skip loading from .env file
    if (getenv('DB_HOST') !== false) {
        return; // Already loaded by hosting platform
    }

    // Local development: Load from .env file
    if (!file_exists($path)) {
        // On Vercel, .env won't exist - that's OK, variables are set by platform
        // Only die if we're in local development without the file
        if (getenv('VERCEL') === false) {
            die("Error: .env file not found at $path. Please create it from .env.example");
        }
        return;
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