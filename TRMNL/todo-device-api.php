<?php
/**
 * todo-device-api.php — Long-lived API key endpoint for TRMNL and other remote devices.
 *
 * Usage:  GET /todo-device-api.php?key=YOUR_DEVICE_KEY
 * Returns JSON:
 *   {
 *     "urgent": [ { text, priority, due_label, tags[] }, … ],
 *     "rest":   [ { text, priority, due_label, tags[] }, … ],
 *     "total":  N
 *   }
 *
 * "urgent" = priority 1–2, OR due within 3 days (incl. overdue)
 * "rest"   = everything else, sorted by priority then text
 * Both columns exclude done items and recurring items (those live in the calendar).
 *
 * ── Setup ─────────────────────────────────────────────────────────────────────
 * 1. Set DEVICE_KEY to any long random string (e.g. `openssl rand -hex 32`)
 * 2. Verify TODOS_FILE points to wherever todo-api.php stores its data
 * 3. Drop this file in the same directory as kaslo-weather.php
 * 4. Add ?key=YOUR_DEVICE_KEY to the polling URL in TRMNL
 * ─────────────────────────────────────────────────────────────────────────────
 */

// ── Config ────────────────────────────────────────────────────────────────────

/** Long-lived secret — treat like a password. Set once, rotate if compromised. */
const DEVICE_KEY = 'REPLACE_WITH_YOUR_SECRET_KEY';

/**
 * Path to the JSON file where todo-api.php persists todos.
 * Adjust this to match your actual storage path.
 * Common locations: __DIR__.'/data/todos.json'  or  __DIR__.'/todos.json'
 */
const TODOS_FILE = __DIR__ . '/data/todos.json';

/** Safety cap — never return more than this many items. */
const MAX_ITEMS = 30;

// ── Headers ───────────────────────────────────────────────────────────────────
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('X-Content-Type-Options: nosniff');

// ── Auth ──────────────────────────────────────────────────────────────────────
$supplied = trim($_GET['key'] ?? '');
if ($supplied === '' || !hash_equals(DEVICE_KEY, $supplied)) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// ── Read todos ────────────────────────────────────────────────────────────────
if (!file_exists(TODOS_FILE) || !is_readable(TODOS_FILE)) {
    http_response_code(503);
    echo json_encode(['error' => 'Data file not found', 'path' => TODOS_FILE]);
    exit;
}

$raw = file_get_contents(TODOS_FILE);
$all = json_decode($raw, true);

if (!is_array($all)) {
    http_response_code(500);
    echo json_encode(['error' => 'JSON parse error']);
    exit;
}

// ── Helpers ───────────────────────────────────────────────────────────────────

/**
 * Format a YYYY-MM-DD due date into a human-readable label.
 * Returns '' if $due is empty/null.
 */
function fmt_due(?string $due): string {
    if (empty($due)) return '';
    $today    = (new DateTime('today'))->format('Y-m-d');
    $tomorrow = (new DateTime('today + 1 day'))->format('Y-m-d');
    if ($due === $today)    return 'Today';
    if ($due === $tomorrow) return 'Tomorrow';
    $dueObj   = new DateTime($due . 'T12:00:00');
    $todayObj = new DateTime('today T12:00:00');
    $days     = (int) $todayObj->diff($dueObj)->format('%r%a'); // negative = overdue
    if ($days < 0) return abs($days) . 'd overdue';
    return $dueObj->format('M j');  // e.g. "Apr 18"
}

/**
 * Returns true if an item belongs in the "urgent" (left) column:
 *   - Priority 1 or 2
 *   - Due within 3 days OR overdue
 */
function is_urgent(array $item): bool {
    $pri = (int) ($item['priority'] ?? 5);
    if ($pri <= 2) return true;
    if (!empty($item['due'])) {
        $in3 = (new DateTime('today + 3 days'))->format('Y-m-d');
        if ($item['due'] <= $in3) return true;
    }
    return false;
}

/**
 * Comparison for urgent column: overdue/soonest first, then priority, then alpha.
 */
function cmp_urgent(array $a, array $b): int {
    $da = $a['due'] ?? null;
    $db = $b['due'] ?? null;
    if ($da && $db)  return strcmp($da, $db);
    if ($da && !$db) return -1;
    if (!$da && $db) return 1;
    $pa = (int)($a['priority'] ?? 5);
    $pb = (int)($b['priority'] ?? 5);
    if ($pa !== $pb) return $pa - $pb;
    return strcmp($a['text'] ?? '', $b['text'] ?? '');
}

/**
 * Comparison for rest column: priority asc, then alpha.
 */
function cmp_rest(array $a, array $b): int {
    $pa = (int)($a['priority'] ?? 5);
    $pb = (int)($b['priority'] ?? 5);
    if ($pa !== $pb) return $pa - $pb;
    return strcmp($a['text'] ?? '', $b['text'] ?? '');
}

// ── Filter ────────────────────────────────────────────────────────────────────
// Exclude: done, recurring-by-weekday, recurring-by-day-of-month.
// These match the same rules used in the browser's loadTodos() filtering.
$active = array_filter($all, function (array $item): bool {
    if (!empty($item['done'])) return false;
    $rw = $item['recurWeekday'] ?? null;
    $rd = $item['recurDay']     ?? null;
    if ($rw !== null && $rw !== '') return false;
    if ($rd !== null && $rd !== '') return false;
    return true;
});
$active = array_values(array_slice($active, 0, MAX_ITEMS));

// ── Classify, format, sort ────────────────────────────────────────────────────
$urgent = [];
$rest   = [];

foreach ($active as $item) {
    $out = [
        'text'      => $item['text'] ?? '',
        'priority'  => (int) ($item['priority'] ?? 5),
        'due_label' => fmt_due($item['due'] ?? null),
        'tags'      => array_values((array)($item['tags'] ?? [])),
    ];
    if (is_urgent($item)) {
        $urgent[] = $out;
    } else {
        $rest[] = $out;
    }
}

usort($urgent, 'cmp_urgent');
usort($rest,   'cmp_rest');

// ── Output ────────────────────────────────────────────────────────────────────
echo json_encode([
    'urgent' => $urgent,
    'rest'   => $rest,
    'total'  => count($urgent) + count($rest),
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
