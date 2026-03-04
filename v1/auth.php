<?php
declare(strict_types=1);

/**
 * Shared authentication / session helpers for the Kaslo dashboard.
 *
 * - Password-based login using PHP sessions
 * - Inactivity timeout
 * - Separate guards for pages (HTML) and APIs (JSON)
 *
 * Configure the login password by changing LOGIN_PASSWORD below,
 * then upload this file along with the rest of the dashboard.
 */

// --- Session setup ---------------------------------------------------------

$isHttps = (
    (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (isset($_SERVER['SERVER_PORT']) && (string)$_SERVER['SERVER_PORT'] === '443')
);

// Ensure session cookies are HTTP-only and scoped to this site
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'domain'   => '',
        'secure'   => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

// --- Configuration ---------------------------------------------------------

// Single shared password for the dashboard.
// CHANGE THIS IMMEDIATELY AFTER DEPLOYING.
const LOGIN_PASSWORD = 'test123';

// Inactivity timeout in seconds (e.g. 1800 = 30 minutes)
const SESSION_TIMEOUT = 1800;

// --- Helpers ---------------------------------------------------------------

function auth_is_logged_in(): bool
{
    if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
        return false;
    }
    if (!isset($_SESSION['last_activity'])) {
        return false;
    }

    $last = (int)$_SESSION['last_activity'];
    if (time() - $last > SESSION_TIMEOUT) {
        auth_logout();
        return false;
    }

    $_SESSION['last_activity'] = time();
    return true;
}

/**
 * Guard for HTML pages — redirects to login.php when not authenticated.
 */
function auth_require_page(): void
{
    if (auth_is_logged_in()) {
        return;
    }

    $target = $_SERVER['REQUEST_URI'] ?? '/kaslo-weather.php';
    header('Location: login.php?redirect=' . urlencode($target));
    exit;
}

/**
 * Guard for JSON / API endpoints — returns HTTP 401 JSON instead of redirecting.
 */
function auth_require_api(): void
{
    if (auth_is_logged_in()) {
        return;
    }

    http_response_code(401);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'Unauthorized'], JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Attempt to log in with the given password.
 */
function auth_attempt_login(string $password): bool
{
    // Constant-time compare to avoid timing side channels
    if (!hash_equals(LOGIN_PASSWORD, $password)) {
        return false;
    }

    session_regenerate_id(true);
    $_SESSION['logged_in']     = true;
    $_SESSION['last_activity'] = time();
    return true;
}

/**
 * Destroy the current session and its cookie.
 */
function auth_logout(): void
{
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'] ?? '/',
            $params['domain'] ?? '',
            $params['secure'] ?? false,
            $params['httponly'] ?? true
        );
    }

    if (session_status() === PHP_SESSION_ACTIVE) {
        session_destroy();
    }
}

