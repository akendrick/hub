<?php
// ── weather-cache.php ─────────────────────────────────────────────────────
// Simple JSON cache store for kaslo-weather.php
// GET  ?key=forecast|ical|obs  → returns cached JSON (200) or 404 if not cached
// POST ?key=forecast|ical|obs  → writes request body as cache file; returns {"ok":true}
//
// Cache files live in  ./cache/<key>.json  (sibling dir next to this file).
// No authentication required — this is public weather data.
// File writes are atomic via temp-file + rename to avoid torn reads.
// ─────────────────────────────────────────────────────────────────────────

$ALLOWED = ['forecast', 'ical', 'obs'];
$CACHE_DIR = __DIR__ . '/cache/';
$MAX_BODY  = 2 * 1024 * 1024; // 2 MB safety limit

// CORS — allow same-origin JS to call from any scheme/port during dev
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

$key = preg_replace('/[^a-z]/', '', strtolower($_GET['key'] ?? ''));
if (!in_array($key, $ALLOWED, true)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid key. Allowed: ' . implode(', ', $ALLOWED)]);
    exit;
}

$file = $CACHE_DIR . $key . '.json';

// ── CREATE cache dir if missing ───────────────────────────────────────────
if (!is_dir($CACHE_DIR)) {
    if (!mkdir($CACHE_DIR, 0755, true)) {
        http_response_code(500);
        echo json_encode(['error' => 'Cannot create cache directory']);
        exit;
    }
}

header('Content-Type: application/json; charset=utf-8');

// ── POST: write cache ─────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $body = file_get_contents('php://input');

    if ($body === false || strlen($body) === 0) {
        http_response_code(400); echo json_encode(['error' => 'Empty body']); exit;
    }
    if (strlen($body) > $MAX_BODY) {
        http_response_code(413); echo json_encode(['error' => 'Body too large']); exit;
    }
    // Validate JSON before writing
    if (json_decode($body) === null) {
        http_response_code(400); echo json_encode(['error' => 'Invalid JSON']); exit;
    }

    // Atomic write: temp file → rename
    $tmp = $file . '.tmp.' . getmypid();
    if (file_put_contents($tmp, $body, LOCK_EX) === false) {
        @unlink($tmp);
        http_response_code(500); echo json_encode(['error' => 'Write failed']); exit;
    }
    if (!rename($tmp, $file)) {
        @unlink($tmp);
        http_response_code(500); echo json_encode(['error' => 'Rename failed']); exit;
    }

    echo json_encode(['ok' => true, 'key' => $key, 'bytes' => strlen($body), 'ts' => time()]);
    exit;
}

// ── GET: read cache ───────────────────────────────────────────────────────
if (!file_exists($file) || !is_readable($file)) {
    http_response_code(404);
    echo json_encode(['error' => 'No cache for key: ' . $key]);
    exit;
}

$mtime = filemtime($file);
$age   = time() - $mtime;

// ETags for conditional requests — avoids re-sending unchanged data
$etag = '"' . md5($key . $mtime) . '"';
header('ETag: ' . $etag);
header('Cache-Control: no-cache');

if (isset($_SERVER['HTTP_IF_NONE_MATCH']) && $_SERVER['HTTP_IF_NONE_MATCH'] === $etag) {
    http_response_code(304); exit;
}

$content = file_get_contents($file);
if ($content === false) {
    http_response_code(500); echo json_encode(['error' => 'Read failed']); exit;
}

// Wrap with metadata the JS needs: data + server timestamp
echo json_encode([
    'ok'  => true,
    'key' => $key,
    'ts'  => $mtime,   // unix timestamp of when cache was last written
    'age' => $age,     // seconds old
    'd'   => json_decode($content), // the actual cached payload
]);
