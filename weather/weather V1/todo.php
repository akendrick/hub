<?php
/**
 * todo.php — Kaslo to-do backend
 *
 * IMPORTANT — file permissions:
 *   After uploading, run once in your browser: todo.php?action=diag
 *   If writable=false, SSH in and run:  chmod 664 todo.json
 *   Or let this script create the file:  chmod 775 the directory
 *
 * Endpoints:
 *   GET  ?              → resolved list (dashboard)
 *   GET  ?action=raw    → full list (manager UI)
 *   GET  ?action=diag   → permission diagnostics
 *   POST ?action=save   → save full list
 */

define('DATA_FILE', __DIR__ . '/todo.json');

// ── Headers ──────────────────────────────────────────────────────
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate');
// Allow same-origin and local requests
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if ($origin) header('Access-Control-Allow-Origin: ' . $origin);
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Credentials: true');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

// ── Helpers ───────────────────────────────────────────────────────
function load_todos() {
    if (!file_exists(DATA_FILE)) return [];
    $raw = @file_get_contents(DATA_FILE);
    if ($raw === false || trim($raw) === '') return [];
    $d = json_decode($raw, true);
    return is_array($d) ? $d : [];
}

function save_todos($items) {
    $json = json_encode(
        array_values($items),
        JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );

    // If file doesn't exist, try to create it
    if (!file_exists(DATA_FILE)) {
        $created = @file_put_contents(DATA_FILE, '[]');
        if ($created !== false) @chmod(DATA_FILE, 0664);
    }

    if (!is_writable(DATA_FILE)) {
        return 'todo.json is not writable. SSH in and run: chmod 664 todo.json';
    }

    $ok = file_put_contents(DATA_FILE, $json, LOCK_EX);
    if ($ok === false) {
        $err = error_get_last();
        return 'Write failed: ' . ($err['message'] ?? 'unknown error');
    }
    return true;
}

function sanitise($item) {
    $valid_p = ["IMP!", "Today", "Soon", "Don't Forget"];
    $tags = [];
    if (!empty($item['tags']) && is_array($item['tags'])) {
        foreach ($item['tags'] as $t) {
            $t = mb_strtoupper(mb_substr(trim(strip_tags((string)$t)), 0, 30));
            if ($t !== '') $tags[] = $t;
        }
        $tags = array_values(array_unique($tags));
    }
    return [
        'id'           => preg_replace('/[^a-zA-Z0-9_-]/', '',
                            substr((string)($item['id'] ?? ''), 0, 40)),
        'text'         => mb_substr(strip_tags((string)($item['text'] ?? '')), 0, 400),
        'priority'     => in_array($item['priority'] ?? '', $valid_p)
                            ? $item['priority'] : "Don't Forget",
        'tags'         => $tags,
        'due'          => preg_match('/^\d{4}-\d{2}-\d{2}$/', $item['due'] ?? '')
                            ? $item['due'] : null,
        'recurWeekday' => (isset($item['recurWeekday']) && is_numeric($item['recurWeekday']))
                            ? (int)$item['recurWeekday'] : null,
        'recurDay'     => (isset($item['recurDay']) && is_numeric($item['recurDay']))
                            ? (int)$item['recurDay'] : null,
        'done'         => (bool)($item['done'] ?? false),
        'created'      => preg_match('/^\d{4}-\d{2}-\d{2}$/', $item['created'] ?? '')
                            ? $item['created'] : date('Y-m-d'),
        'notes'        => mb_substr(strip_tags((string)($item['notes'] ?? '')), 0, 500),
    ];
}

function resolve_for_display($items) {
    $today = new DateTime();
    $dow   = (int)$today->format('w');  // 0=Sun
    $dom   = (int)$today->format('j');  // day-of-month
    $out   = [];
    foreach ($items as $item) {
        if (!empty($item['done'])) continue;
        if (isset($item['recurWeekday']) && $item['recurWeekday'] !== null) {
            if ((int)$item['recurWeekday'] === $dow) $out[] = $item;
            continue;
        }
        if (isset($item['recurDay']) && $item['recurDay'] !== null) {
            if ((int)$item['recurDay'] === $dom) $out[] = $item;
            continue;
        }
        $out[] = $item;
    }
    $order = ['IMP!' => 0, 'Today' => 1, 'Soon' => 2, "Don't Forget" => 3];
    usort($out, function($a, $b) use ($order) {
        $pa = $order[$a['priority'] ?? "Don't Forget"] ?? 3;
        $pb = $order[$b['priority'] ?? "Don't Forget"] ?? 3;
        if ($pa !== $pb) return $pa - $pb;
        return strcmp($a['due'] ?? 'z', $b['due'] ?? 'z');
    });
    return array_values($out);
}

// ── Routing ───────────────────────────────────────────────────────
$method = $_SERVER['REQUEST_METHOD'];
$action = trim($_GET['action'] ?? '');

// Diagnostics — visit todo.php?action=diag in browser to debug
if ($method === 'GET' && $action === 'diag') {
    $dir = dirname(DATA_FILE);
    echo json_encode([
        'data_file'       => DATA_FILE,
        'file_exists'     => file_exists(DATA_FILE),
        'file_readable'   => is_readable(DATA_FILE),
        'file_writable'   => file_exists(DATA_FILE) ? is_writable(DATA_FILE) : 'n/a (not created yet)',
        'dir_writable'    => is_writable($dir),
        'dir_path'        => $dir,
        'php_version'     => PHP_VERSION,
        'current_user'    => function_exists('posix_getpwuid')
                               ? (posix_getpwuid(posix_geteuid())['name'] ?? 'unknown')
                               : 'posix not available',
        'item_count'      => count(load_todos()),
        'suggestion'      => (!file_exists(DATA_FILE) && !is_writable($dir))
                               ? 'Create todo.json manually and run: chmod 664 todo.json'
                               : (file_exists(DATA_FILE) && !is_writable(DATA_FILE)
                                   ? 'Run: chmod 664 todo.json'
                                   : 'OK'),
    ], JSON_PRETTY_PRINT);
    exit;
}

// GET resolved (dashboard)
if ($method === 'GET' && $action === '') {
    echo json_encode(resolve_for_display(load_todos()));
    exit;
}

// GET raw (manager UI — full unfiltered list)
if ($method === 'GET' && $action === 'raw') {
    echo json_encode(load_todos());
    exit;
}

// POST save
if ($method === 'POST' && $action === 'save') {
    $raw  = file_get_contents('php://input');
    $body = json_decode($raw, true);

    if (!is_array($body)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid JSON. Received: ' . substr($raw, 0, 80)]);
        exit;
    }

    $clean = [];
    foreach ($body as $item) {
        $s = sanitise($item);
        if ($s['id'] !== '' && $s['text'] !== '') $clean[] = $s;
    }

    $result = save_todos($clean);
    if ($result === true) {
        echo json_encode(['ok' => true, 'count' => count($clean)]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => $result]);
    }
    exit;
}

http_response_code(400);
echo json_encode(['error' => 'Unknown action: ' . htmlspecialchars($action)]);
