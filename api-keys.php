<?php
/**
 * api-keys.php — Manage per-device API keys (web dashboard, session login only)
 *
 * AUTHENTICATION
 *   Session login only — device keys cannot manage themselves.
 *
 * ENDPOINTS
 *   GET    /api-keys.php              → list all keys (key_hash masked, raw key never shown)
 *   POST   /api-keys.php              → create new key { "device_name": "My iPhone" }
 *                                        Response includes raw key ONCE — store it immediately
 *   DELETE /api-keys.php?id=<id>      → revoke a key permanently
 *
 * KEY FORMAT
 *   kw_  followed by 64 lowercase hex characters (= 256 bits of entropy)
 *   Only the SHA-256 hash is stored server-side.
 *
 * USAGE (from iPhone app or curl)
 *   curl -X POST https://knotwork.ca/device-api.php \
 *        -H "Authorization: Bearer kw_<your64hexkey>" \
 *        -H "Content-Type: application/json" \
 *        -d '{"text":"Buy milk","priority":2}'
 */
declare(strict_types=1);

require __DIR__ . '/auth.php';
auth_require_api();   // session login only — device keys cannot create/delete keys

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$method = $_SERVER['REQUEST_METHOD'];
$id     = trim((string)($_GET['id'] ?? ''));

function json_err(int $code, string $msg): never
{
    http_response_code($code);
    echo json_encode(['error' => $msg], JSON_UNESCAPED_UNICODE);
    exit;
}

switch ($method) {

    // ── GET — list all keys (raw key never returned, only metadata) ───────────
    case 'GET':
        $keys = api_keys_load();
        // Mask key_hash so it's not directly exploitable even if response is intercepted
        $masked = array_map(function (array $k): array {
            $display = $k;
            unset($display['key_hash']);
            $display['key_preview'] = 'kw_' . substr($k['key_hash'] ?? '', 0, 8) . '…';
            return $display;
        }, $keys);
        echo json_encode(array_values($masked), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        break;

    // ── POST — create new key ─────────────────────────────────────────────────
    case 'POST':
        $raw = @file_get_contents('php://input');
        $body = $raw ? json_decode($raw, true) : [];
        if (!is_array($body)) json_err(400, 'Request body must be a JSON object');

        $deviceName = trim((string)($body['device_name'] ?? ''));
        if ($deviceName === '') json_err(422, '"device_name" is required');
        if (mb_strlen($deviceName) > 100) json_err(422, '"device_name" max 100 characters');

        // Generate 256-bit key as kw_<64 hex chars>
        $rawKey = 'kw_' . bin2hex(random_bytes(32));
        $hash   = hash('sha256', $rawKey);

        $record = [
            'id'          => uniqid('key_', true),
            'key_hash'    => $hash,
            'device_name' => $deviceName,
            'created'     => date('c'),
            'last_used'   => null,
        ];

        $keys   = api_keys_load();
        $keys[] = $record;

        if (!api_keys_save($keys)) {
            // Save failed — most likely a file-permission problem.
            // The web-server user needs write access to the directory containing api-keys.json.
            json_err(500,
                'Could not write api-keys.json — check that the web server has write '
                . 'permission on the directory: ' . dirname(API_KEYS_FILE)
            );
        }

        http_response_code(201);
        echo json_encode([
            'id'          => $record['id'],
            'device_name' => $record['device_name'],
            'created'     => $record['created'],
            'api_key'     => $rawKey,   // ← shown ONCE, never retrievable again
            'note'        => 'Store this key securely. It cannot be retrieved again.',
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        break;

    // ── DELETE — revoke a key ─────────────────────────────────────────────────
    case 'DELETE':
        if ($id === '') json_err(400, 'Missing ?id= parameter');
        $keys   = api_keys_load();
        $before = count($keys);
        $keys   = array_values(array_filter($keys, fn($k) => ($k['id'] ?? '') !== $id));
        if (count($keys) === $before) json_err(404, "Key '$id' not found");
        api_keys_save($keys);
        echo json_encode(['revoked' => $id], JSON_UNESCAPED_UNICODE);
        break;

    default:
        json_err(405, 'Method not allowed');
}
