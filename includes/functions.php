<?php
// This file contains various utility functions that can be used throughout the application

function sanitizeInput($data) {
    if (!is_string($data)) {
        return $data;
    }
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

function formatCurrency($amount) {
    return number_format($amount, 2, '.', '');
}

function redirectTo($url) {
    $url = str_replace(["\r", "\n"], '', $url);
    header('Location: ' . $url);
    session_write_close();
    exit();
}

function flashMessage($message) {
    if (!isset($_SESSION['flash_messages'])) {
        $_SESSION['flash_messages'] = [];
    }
    $_SESSION['flash_messages'][] = $message;
}

function getFlashMessages() {
    if (isset($_SESSION['flash_messages'])) {
        $messages = $_SESSION['flash_messages'];
        unset($_SESSION['flash_messages']);
        return $messages;
    }
    return [];
}

/*
 * =============================================
 * CSRF PROTECTION
 * =============================================
 */

function getCsrfToken()
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validateCsrfToken($token)
{
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

function csrfField()
{
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(getCsrfToken(), ENT_QUOTES, 'UTF-8') . '">';
}

function requireCsrfToken()
{
    $token = $_POST['csrf_token'] ?? '';
    if (!validateCsrfToken($token)) {
        error_log('CSRF validation failed for ' . ($_SERVER['REQUEST_URI'] ?? 'unknown') . ' from IP ' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
        flashMessage('Invalid or expired session. Please try again.');
        // Only redirect to a same-origin referer to prevent open redirects
        $referer = str_replace(["\r", "\n"], '', $_SERVER['HTTP_REFERER'] ?? '');
        $fallback = 'index.php';
        $host = $_SERVER['HTTP_HOST'] ?? '';
        $redirect = $fallback;
        if ($referer !== '') {
            $parts = parse_url($referer);
            if ($parts !== false && isset($parts['host']) && $parts['host'] === $host && !empty($parts['path'])) {
                $redirect = $parts['path'] . (isset($parts['query']) ? '?' . $parts['query'] : '');
            }
        }
        header('Location: ' . $redirect);
        session_write_close();
        exit();
    }
}

function getOnlineStatus($user)
{
    $status = $user['status'] ?? 'inactive';

    if (strtolower($status) === 'active') {
        return ['label' => 'Active', 'css' => 'status-active', 'icon' => '🟢'];
    }

    return ['label' => 'Inactive', 'css' => 'status-inactive', 'icon' => '🔴'];
}

function renderOnlineStatus($user)
{
    $s = getOnlineStatus($user);
    return '<span class="status-badge ' . $s['css'] . '">' . $s['icon'] . ' ' . $s['label'] . '</span>';
}

function requireCsrfTokenJson()
{
    $token = $_POST['csrf_token'] ?? '';
    if (!validateCsrfToken($token)) {
        error_log('CSRF validation failed for AJAX request: ' . ($_SERVER['REQUEST_URI'] ?? 'unknown'));
        http_response_code(419);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Session expired. Please refresh the page.']);
        session_write_close();
        exit();
    }
}

/*
 * =============================================
 * RATE LIMITING
 * =============================================
 */

function checkRateLimit($key, $maxAttempts = 10, $windowSeconds = 60)
{
    $rate_key = 'rate_' . $key;
    $attempts = $_SESSION[$rate_key] ?? ['count' => 0, 'first_at' => time()];

    if (time() - $attempts['first_at'] > $windowSeconds) {
        $attempts = ['count' => 0, 'first_at' => time()];
    }

    if ($attempts['count'] >= $maxAttempts) {
        return false;
    }

    $attempts['count']++;
    $_SESSION[$rate_key] = $attempts;
    return true;
}

/*
 * =============================================
 * SERVER-SIDE (IP-KEYED) RATE LIMITING
 * =============================================
 *
 * Unlike checkRateLimit() above (which is session-based and therefore
 * bypassable by discarding the session cookie), this tracks attempts in the
 * database keyed by IP address, so it survives across sessions. Intended for
 * pre-authentication endpoints like login where the caller has no session
 * identity to trust yet.
 */

function checkIpRateLimit(PDO $pdo, string $ip, int $maxAttempts = 5, int $windowSeconds = 60): bool
{
    try {
        $stmt = $pdo->prepare("SELECT attempt_count, first_attempt_at FROM login_rate_limits WHERE ip_address = ?");
        $stmt->execute([$ip]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        $now = time();

        if (!$row || ($now - strtotime($row['first_attempt_at'])) > $windowSeconds) {
            $stmt = $pdo->prepare("
                INSERT INTO login_rate_limits (ip_address, attempt_count, first_attempt_at, updated_at)
                VALUES (?, 0, NOW(), NOW())
                ON DUPLICATE KEY UPDATE attempt_count = 0, first_attempt_at = NOW(), updated_at = NOW()
            ");
            $stmt->execute([$ip]);
            return true;
        }

        if ((int)$row['attempt_count'] >= $maxAttempts) {
            return false;
        }

        return true;
    } catch (PDOException $e) {
        // If the rate-limit table is missing (e.g. migration not yet applied),
        // create it once and allow this request through rather than locking
        // out login entirely.
        try {
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS login_rate_limits (
                    ip_address VARCHAR(45) NOT NULL PRIMARY KEY,
                    attempt_count INT NOT NULL DEFAULT 0,
                    first_attempt_at DATETIME NOT NULL,
                    updated_at DATETIME NOT NULL
                )
            ");
        } catch (PDOException $e2) {
            error_log('checkIpRateLimit: failed to create login_rate_limits table: ' . $e2->getMessage());
        }
        return true;
    }
}

function recordIpRateLimitAttempt(PDO $pdo, string $ip): void
{
    try {
        $stmt = $pdo->prepare("
            INSERT INTO login_rate_limits (ip_address, attempt_count, first_attempt_at, updated_at)
            VALUES (?, 1, NOW(), NOW())
            ON DUPLICATE KEY UPDATE attempt_count = attempt_count + 1, updated_at = NOW()
        ");
        $stmt->execute([$ip]);
    } catch (PDOException $e) {
        error_log('recordIpRateLimitAttempt failed: ' . $e->getMessage());
    }
}

function resetIpRateLimit(PDO $pdo, string $ip): void
{
    try {
        $stmt = $pdo->prepare("DELETE FROM login_rate_limits WHERE ip_address = ?");
        $stmt->execute([$ip]);
    } catch (PDOException $e) {
        error_log('resetIpRateLimit failed: ' . $e->getMessage());
    }
}
?>