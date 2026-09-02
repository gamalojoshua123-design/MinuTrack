<?php
/**
 * Load environment variables from a .env file (project root).
 *
 * .env is git-ignored and should never be committed. Example file:
 *   GROQ_API_KEY=your_groq_key_here
 *
 * Priority: real process environment > .env file > ''.
 */
function mb_load_env_file(string $dir): void {
    $file = rtrim($dir, '/\\') . DIRECTORY_SEPARATOR . '.env';
    if (!is_file($file) || !is_readable($file)) {
        return;
    }
    $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) return;
    foreach ($lines as $line) {
        // Strip UTF-8 BOM if present (common when the file is edited on Windows)
        if (str_starts_with($line, "\xEF\xBB\xBF")) {
            $line = substr($line, 3);
        }
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
            continue;
        }
        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);
        // Strip surrounding quotes if present
        if (strlen($value) >= 2 && (($value[0] === '"' && substr($value, -1) === '"') || ($value[0] === "'" && substr($value, -1) === "'"))) {
            $value = substr($value, 1, -1);
        }
        if ($key !== '' && preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $key) && getenv($key) === false) {
            putenv($key . '=' . $value);
            $_ENV[$key] = $value;
        }
    }
}

mb_load_env_file(__DIR__);

// Groq API key: real environment first, then .env, then '' (surfaced as a clear config error later)
if (!defined('GROQ_API_KEY')) {
    $groqKey = getenv('GROQ_API_KEY');
    define('GROQ_API_KEY', $groqKey !== false && $groqKey !== '' ? $groqKey : '');
}

// Database configuration (from environment variables with safe fallbacks)
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_NAME', getenv('DB_NAME') ?: 'pos_system');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');

// Application settings
define('APP_NAME', 'Minute Burger POS');
define('APP_VERSION', '1.0.0');

// Path definitions
define('BASE_PATH', __DIR__ . '/');
define('INCLUDES_PATH', __DIR__ . '/includes/');
define('ADMIN_PATH', BASE_PATH . 'admin/');

// Security headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://unpkg.com; font-src 'self' https://fonts.gstatic.com https://unpkg.com; img-src 'self' data:; connect-src 'self'");

// Session hardening
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_samesite', 'Lax');
ini_set('session.use_strict_mode', 1);
ini_set('session.use_only_cookies', 1);

// Session settings
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

?>