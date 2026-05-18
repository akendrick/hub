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
 * "urgent"    = priority 1–2, OR due within 3 days (incl. overdue)
 * "important" = priority 3, OR due within 7 days (not already urgent)
 * "rest"      = everything else, sorted by priority then text
 * All buckets exclude done items and recurring items.
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
const DEVICE_KEY = 'kw_40e818911c1980bcd56dc4aff37a820f811990a130eb771fd9e21a536edc55ea';

/**
 * Path to the JSON file where todo-api.php persists todos.
 */
const TODOS_FILE = __DIR__ . '/todo.json';

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
 * Returns true if an item belongs in the "urgent" bucket:
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
 * Returns true if an item belongs in the "important" bucket
 * (not already urgent):
 *   - Priority 3
 *   - Due within 7 days
 */
function is_important(array $item): bool {
    $pri = (int) ($item['priority'] ?? 5);
    if ($pri === 3) return true;
    if (!empty($item['due'])) {
        $in7 = (new DateTime('today + 7 days'))->format('Y-m-d');
        if ($item['due'] <= $in7) return true;
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

/**
 * Build the sub-info line shown beneath a task: "Due · TAG1 TAG2"
 * Returns '' if both due and tags are empty.
 */
function fmt_meta(string $due_label, array $tags): string {
    $parts = [];
    if ($due_label !== '') $parts[] = $due_label;
    $tag_str = implode(' ', array_map('strtoupper', array_filter($tags)));
    if ($tag_str !== '') $parts[] = $tag_str;
    return implode('  ·  ', $parts);
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
$urgent    = [];
$important = [];
$rest      = [];

foreach ($active as $item) {
    $out = [
        'text'      => $item['text'] ?? '',
        'priority'  => (int) ($item['priority'] ?? 5),
        'due_label' => fmt_due($item['due'] ?? null),
        'tags'      => array_values((array)($item['tags'] ?? [])),
    ];
    if (is_urgent($item)) {
        $urgent[] = $out;
    } elseif (is_important($item)) {
        $important[] = $out;
    } else {
        $rest[] = $out;
    }
}

usort($urgent,    'cmp_urgent');
usort($important, 'cmp_rest');
usort($rest,      'cmp_rest');

// ── Flatten to top-level scalars — TRMNL Liquid needs no IDX_0, no arrays ────
// u0_text/u0_meta … u3  (urgent, up to 4)
// i0_text/i0_meta … i5  (important, up to 6)
// r0_text/r0_meta … r7  (rest, up to 8)
// *_meta = pre-formatted "Due · TAG1 TAG2" sub-line ('' when empty)
// *_more = '' or '+N more' string (truthy check works in Liquid)
$flat = ['total' => count($urgent) + count($important) + count($rest)];

$flat['u_count'] = count($urgent);
for ($i = 0; $i < 4; $i++) {
    $flat["u{$i}_text"] = $urgent[$i]['text']      ?? '';
    $flat["u{$i}_due"]  = $urgent[$i]['due_label'] ?? '';
    $flat["u{$i}_meta"] = isset($urgent[$i])
        ? fmt_meta($urgent[$i]['due_label'], $urgent[$i]['tags'])
        : '';
}
$flat['u_more'] = count($urgent) > 4 ? '+' . (count($urgent) - 4) . ' more' : '';

$flat['i_count'] = count($important);
for ($i = 0; $i < 6; $i++) {
    $flat["i{$i}_text"] = $important[$i]['text']      ?? '';
    $flat["i{$i}_due"]  = $important[$i]['due_label'] ?? '';
    $flat["i{$i}_meta"] = isset($important[$i])
        ? fmt_meta($important[$i]['due_label'], $important[$i]['tags'])
        : '';
}
$flat['i_more'] = count($important) > 6 ? '+' . (count($important) - 6) . ' more' : '';

$flat['r_count'] = count($rest);
for ($i = 0; $i < 8; $i++) {
    $flat["r{$i}_text"] = $rest[$i]['text']      ?? '';
    $flat["r{$i}_due"]  = $rest[$i]['due_label'] ?? '';
    $flat["r{$i}_meta"] = isset($rest[$i])
        ? fmt_meta($rest[$i]['due_label'], $rest[$i]['tags'])
        : '';
}
$flat['r_more'] = count($rest) > 8 ? '+' . (count($rest) - 8) . ' more' : '';

// ── Output ────────────────────────────────────────────────────────────────────
echo json_encode($flat, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
