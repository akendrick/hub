<?php
declare(strict_types=1);

/**
 * Shared authentication helpers for the Kaslo dashboard.
 *
 * TWO authentication paths:
 *
 *  1. SESSION AUTH  — password login via the web browser.
 *                     Use auth_require_page() or auth_require_api().
 *
 *  2. DEVICE API KEY — long-lived opaque key issued to remote apps/devices.
 *                      Keys live in api-keys.json (never exposed to the browser).
 *                      Use auth_require_device_api() on device-facing endpoints.
 *                      Clients send:  Authorization: Bearer kw_<hex64>
 *                       OR (fallback): ?api_key=kw_<hex64>
 */

// ---------------------------------------------------------------------------
// Configuration
// ---------------------------------------------------------------------------

/** Single shared password for the web dashboard. Change after deploying. */
const LOGIN_PASSWORD = 'test123';

/** Session inactivity timeout in seconds (8 hours — suits a full-day dashboard). */
const SESSION_TIMEOUT = 28800;

/** Path to the API key store relative to this file. */
define('API_KEYS_FILE', __DIR__ . '/api-keys.json');

// ---------------------------------------------------------------------------
// Session setup
// ---------------------------------------------------------------------------

$_kw_isHttps = (
    (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (isset($_SERVER['SERVER_PORT']) && (string)$_SERVER['SERVER_PORT'] === '443')
);

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'domain'   => '',
        'secure'   => $_kw_isHttps,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

// ---------------------------------------------------------------------------
// Session helpers
// ---------------------------------------------------------------------------

function auth_is_logged_in(): bool
{
    if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) return false;
    if (!isset($_SESSION['last_activity'])) return false;
    if (time() - (int)$_SESSION['last_activity'] > SESSION_TIMEOUT) {
        auth_logout();
        return false;
    }
    $_SESSION['last_activity'] = time();
    return true;
}

/** Guard for HTML pages — redirects to login.php when not authenticated. */
function auth_require_page(): void
{
    if (auth_is_logged_in()) return;
    $target = $_SERVER['REQUEST_URI'] ?? '/kaslo-weather.php';
    header('Location: login.php?redirect=' . urlencode($target));
    exit;
}

/** Guard for JSON/API endpoints used by the web UI (session only). */
function auth_require_api(): void
{
    if (auth_is_logged_in()) return;
    http_response_code(401);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'Unauthorized'], JSON_UNESCAPED_UNICODE);
    exit;
}

function auth_attempt_login(string $password): bool
{
    if (!hash_equals(LOGIN_PASSWORD, $password)) return false;
    session_regenerate_id(true);
    $_SESSION['logged_in']     = true;
    $_SESSION['last_activity'] = time();
    return true;
}

function auth_logout(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $p['path'] ?? '/', $p['domain'] ?? '',
            $p['secure'] ?? false, $p['httponly'] ?? true);
    }
    if (session_status() === PHP_SESSION_ACTIVE) session_destroy();
}

// ---------------------------------------------------------------------------
// Device API key helpers
// ---------------------------------------------------------------------------

/**
 * Load all API key records from disk.
 * Each record: { id, key_hash, device_name, created, last_used }
 * key_hash = hash('sha256', raw_key) — raw key is never stored.
 *
 * @return list<array<string,string>>
 */
function api_keys_load(): array
{
    if (!is_file(API_KEYS_FILE)) return [];
    $raw = @file_get_contents(API_KEYS_FILE);
    if (!$raw) return [];
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

/**
 * Persist the API key list atomically.
 * Returns true on success, false on any I/O failure (caller decides what to do).
 *
 * @param list<array<string,string>> $keys
 */
function api_keys_save(array $keys): bool
{
    $tmp  = API_KEYS_FILE . '.tmp.' . getmypid();
    $json = json_encode($keys, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

    if ($json === false) return false;

    if (file_put_contents($tmp, $json, LOCK_EX) === false) {
        // Write failed — usually a permissions problem.
        // Check that the web-server user has write access to the directory.
        return false;
    }

    if (!rename($tmp, API_KEYS_FILE)) {
        @unlink($tmp);
        return false;
    }

    return true;
}

/**
 * Validate an incoming raw API key.
 * On success returns the key record (and updates last_used on disk).
 * On failure returns null.
 *
 * @return array<string,string>|null
 */
function auth_validate_api_key(string $rawKey): ?array
{
    if (!preg_match('/^kw_[0-9a-f]{64}$/', $rawKey)) return null;

    $keys  = api_keys_load();
    $hash  = hash('sha256', $rawKey);
    $found = null;

    foreach ($keys as &$record) {
        if (!isset($record['key_hash'])) continue;
        if (hash_equals($record['key_hash'], $hash)) {
            $record['last_used'] = date('c');
            $found = $record;
            break;
        }
    }
    unset($record);

    if ($found) {
        @api_keys_save($keys); // fire-and-forget; don't abort on failure
    }

    return $found;
}

/**
 * Extract the raw API key from the current request.
 *
 * Priority: Authorization: Bearer header  >  ?api_key= query param
 *
 * Apache+PHP-FPM strips HTTP_AUTHORIZATION from $_SERVER by default.
 * Workaround requires this in .htaccess (already included in knotwork.htaccess):
 *   RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization},L]
 * This copies the header into REDIRECT_HTTP_AUTHORIZATION (sometimes
 * also into HTTP_AUTHORIZATION depending on Apache version).
 */
function auth_get_request_api_key(): ?string
{
    // 1. Standard CGI / mod_php
    $header = $_SERVER['HTTP_AUTHORIZATION'] ?? null;

    // 2. Apache+PHP-FPM with RewriteRule passthrough (.htaccess fix)
    if (!$header) {
        $header = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? null;
    }

    // 3. apache_request_headers() / getallheaders() — mod_php or some FPM configs
    if (!$header) {
        $fn = function_exists('getallheaders') ? 'getallheaders'
            : (function_exists('apache_request_headers') ? 'apache_request_headers' : null);
        if ($fn) {
            $hdrs   = $fn();
            $header = $hdrs['Authorization'] ?? $hdrs['authorization'] ?? null;
        }
    }

    if ($header && preg_match('/^Bearer\s+(kw_[0-9a-f]{64})$/i', $header, $m)) {
        return $m[1];
    }

    // 4. Query-string fallback (handy for testing; less secure — use HTTPS)
    $qs = (string)($_GET['api_key'] ?? '');
    if (preg_match('/^kw_[0-9a-f]{64}$/', $qs)) return $qs;

    return null;
}

/**
 * Guard for device-facing API endpoints.
 * Accepts ONLY a valid per-device API key — not a session cookie.
 * Sets $GLOBALS['_kw_api_key_record'] on success.
 */
function auth_require_device_api(): void
{
    $rawKey = auth_get_request_api_key();
    if ($rawKey !== null) {
        $record = auth_validate_api_key($rawKey);
        if ($record !== null) {
            $GLOBALS['_kw_api_key_record'] = $record;
            return;
        }
    }
    http_response_code(401);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'error' => 'Invalid or missing API key. '
                 . 'Send "Authorization: Bearer kw_<key>" header.',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}
