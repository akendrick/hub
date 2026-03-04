<?php
/**
 * device-api.php — REST API for remote devices (iPhone app, scripts, etc.)
 *
 * AUTHENTICATION
 *   Every request must carry a valid per-device API key:
 *     Authorization: Bearer kw_<64 hex chars>
 *   Keys are created via api-keys.php (web dashboard, session login only).
 *
 * ENDPOINTS
 *   GET    /device-api.php              → list all non-deleted todos
 *   GET    /device-api.php?id=<id>      → get one todo
 *   POST   /device-api.php              → add one item (server assigns id/created/done)
 *   PATCH  /device-api.php?id=<id>      → update mutable fields of an existing item
 *   DELETE /device-api.php?id=<id>      → permanently remove an item
 *
 * FIELD RULES (POST / PATCH)
 *   text        string, required on POST, 1–500 chars
 *   priority    int 1–5  (1=highest, 5=lowest); default 3; clamped silently
 *   notes       string|null, max 2000 chars
 *   tags        array of strings; each tag max 50 chars; max 20 tags; deduped & sorted
 *   due         "YYYY-MM-DD" string | null; validated
 *   done        bool; ignored on POST (always false); accepted on PATCH
 *   recurWeekday  "Mon"–"Sun" string | null
 *   recurDay    "1"–"31" string | null
 *
 * SERVER-ENFORCED (POST only — clients cannot set these)
 *   id          uniqid('td_', true)
 *   created     ISO 8601 datetime
 *   done        always false on creation
 *
 * RESPONSE FORMAT
 *   Success: HTTP 200 + JSON body described per endpoint
 *   Error:   HTTP 4xx/5xx + { "error": "message" }
 */
declare(strict_types=1);

require __DIR__ . '/auth.php';
auth_require_device_api();                // exits with 401 if key missing/invalid

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

// CORS — allow only same origin (server itself) and optionally localhost for dev
// Uncomment and adjust if your app needs cross-origin access:
// header('Access-Control-Allow-Origin: *');
// header('Access-Control-Allow-Headers: Authorization, Content-Type');
// header('Access-Control-Allow-Methods: GET, POST, PATCH, DELETE, OPTIONS');
// if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

$TODO_FILE = __DIR__ . '/todo.json';
$method    = $_SERVER['REQUEST_METHOD'];
$id        = trim((string)($_GET['id'] ?? ''));

// ── Helpers ──────────────────────────────────────────────────────────────────

function todos_load(string $file): array
{
    if (!is_file($file)) return [];
    $raw = @file_get_contents($file);
    if (!$raw) return [];
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

function todos_save(string $file, array $items): void
{
    $tmp = $file . '.tmp.' . getmypid();
    if (file_put_contents($tmp,
        json_encode(array_values($items),
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX) === false) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to write todo data']);
        exit;
    }
    if (!rename($tmp, $file)) {
        @unlink($tmp);
        http_response_code(500);
        echo json_encode(['error' => 'Failed to finalise todo save']);
        exit;
    }
}

function json_err(int $code, string $msg): never
{
    http_response_code($code);
    echo json_encode(['error' => $msg], JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Normalise recurWeekday to a "Mon"-"Sun" string.
 * Accepts integer 0-6 (web UI format) or the three-letter string.
 * Returns null for any invalid or empty value.
 */
function normalise_recur_weekday(mixed $v): ?string
{
    static $DOW = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];
    if ($v === null || $v === '') return null;
    if (is_int($v) || (is_string($v) && ctype_digit($v))) {
        $i = (int)$v;
        return ($i >= 0 && $i <= 6) ? $DOW[$i] : null;
    }
    if (is_string($v) && preg_match('/^(Mon|Tue|Wed|Thu|Fri|Sat|Sun)$/', $v)) return $v;
    return null;
}

/**
 * Normalise priority to an integer 1–5.
 * Legacy web UI stored string labels; new API uses integers.
 * Returns the integer, or null if unrecognised (caller should default to 3).
 */
function normalise_priority(mixed $v): ?int
{
    if ($v === null || $v === '') return null;
    if (is_int($v) || (is_string($v) && ctype_digit($v))) {
        $i = (int)$v;
        return ($i >= 1 && $i <= 5) ? $i : null;
    }
    // Map legacy string labels → integers
    static $MAP = [
        'IMP!'         => 1,
        'URGENT'       => 1,
        'Today'        => 2,
        'TODAY'        => 2,
        "Don't Forget" => 3,
        "DON'T FORGET" => 3,
        'Low'          => 4,
        'LOW'          => 4,
        'Someday'      => 5,
        'SOMEDAY'      => 5,
    ];
    if (is_string($v)) {
        return $MAP[$v] ?? $MAP[strtoupper($v)] ?? null;
    }
    return null;
}

/**
 * Validate and normalise mutable fields from the request body.
 * Returns only the keys present in $body (for PATCH partial updates).
 *
 * @param array<string,mixed> $body
 * @param bool $requireText  true on POST, false on PATCH
 * @return array<string,mixed>
 */
function validate_fields(array $body, bool $requireText): array
{
    $out = [];

    // text
    if (array_key_exists('text', $body)) {
        $t = trim((string)($body['text'] ?? ''));
        if ($t === '') json_err(422, '"text" must be a non-empty string');
        if (mb_strlen($t) > 500) json_err(422, '"text" exceeds 500 characters');
        $out['text'] = $t;
    } elseif ($requireText) {
        json_err(422, '"text" is required');
    }

    // priority  — clamp to 1..5, default 3
    if (array_key_exists('priority', $body)) {
        $p = filter_var($body['priority'], FILTER_VALIDATE_INT);
        if ($p === false) json_err(422, '"priority" must be an integer');
        $out['priority'] = max(1, min(5, (int)$p));
    } elseif ($requireText) {
        $out['priority'] = 3; // default on creation
    }

    // done — only accepted on PATCH; POST always forces false
    if (!$requireText && array_key_exists('done', $body)) {
        $out['done'] = (bool)$body['done'];
    }

    // notes
    if (array_key_exists('notes', $body)) {
        if ($body['notes'] === null || $body['notes'] === '') {
            $out['notes'] = null;
        } else {
            $n = (string)$body['notes'];
            if (mb_strlen($n) > 2000) json_err(422, '"notes" exceeds 2000 characters');
            $out['notes'] = $n;
        }
    }

    // tags — array of strings, deduped, sorted, max 20, each max 50 chars
    if (array_key_exists('tags', $body)) {
        if (!is_array($body['tags'])) json_err(422, '"tags" must be an array');
        $tags = [];
        foreach ($body['tags'] as $tag) {
            $tag = trim((string)$tag);
            if ($tag === '') continue;
            if (mb_strlen($tag) > 50) json_err(422, 'Each tag must be ≤50 characters');
            $tags[] = $tag;
        }
        $tags = array_values(array_unique($tags));
        sort($tags);
        if (count($tags) > 20) json_err(422, 'Maximum 20 tags per item');
        $out['tags'] = $tags;
    } elseif ($requireText) {
        $out['tags'] = [];
    }

    // due — "YYYY-MM-DD" or null
    if (array_key_exists('due', $body)) {
        if ($body['due'] === null || $body['due'] === '') {
            $out['due'] = null;
        } else {
            $d = (string)$body['due'];
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $d)
                || !checkdate((int)substr($d,5,2),(int)substr($d,8,2),(int)substr($d,0,4))) {
                json_err(422, '"due" must be "YYYY-MM-DD" or null');
            }
            $out['due'] = $d;
        }
    } elseif ($requireText) {
        $out['due'] = null;
    }

    // recurWeekday — accepts "Mon"-"Sun" string OR integer 0-6 (web UI stores ints)
    if (array_key_exists('recurWeekday', $body)) {
        $rw = normalise_recur_weekday($body['recurWeekday']);
        if ($body['recurWeekday'] !== null && $body['recurWeekday'] !== '' && $rw === null) {
            json_err(422, '"recurWeekday" must be Mon|Tue|Wed|Thu|Fri|Sat|Sun, 0-6, or null');
        }
        $out['recurWeekday'] = $rw;
    } elseif ($requireText) {
        $out['recurWeekday'] = null;
    }

    // recurDay — "1"–"31" or null
    if (array_key_exists('recurDay', $body)) {
        if ($body['recurDay'] === null || $body['recurDay'] === '') {
            $out['recurDay'] = null;
        } else {
            $rd = (int)$body['recurDay'];
            if ($rd < 1 || $rd > 31) json_err(422, '"recurDay" must be 1–31 or null');
            $out['recurDay'] = (string)$rd;
        }
    } elseif ($requireText) {
        $out['recurDay'] = null;
    }

    return $out;
}

// ── Request body parsing ──────────────────────────────────────────────────────

function read_body(): array
{
    $raw = @file_get_contents('php://input');
    if (!$raw) json_err(400, 'Empty request body');
    $data = json_decode($raw, true);
    if (!is_array($data)) json_err(400, 'Request body must be a JSON object');
    return $data;
}

// ── Route ─────────────────────────────────────────────────────────────────────

switch ($method) {

    // ── GET ──────────────────────────────────────────────────────────────────
    case 'GET':
        $todos = todos_load($TODO_FILE);
        if ($id !== '') {
            // Get single item
            foreach ($todos as $item) {
                if (($item['id'] ?? '') === $id) {
                    if (isset($item['recurWeekday']) && $item['recurWeekday'] !== null) {
                        $item['recurWeekday'] = normalise_recur_weekday($item['recurWeekday']) ?? $item['recurWeekday'];
                    }
                    if (isset($item['priority'])) {
                        $norm = normalise_priority($item['priority']);
                        if ($norm !== null) $item['priority'] = $norm;
                    }
                    echo json_encode($item, JSON_UNESCAPED_UNICODE);
                    exit;
                }
            }
            json_err(404, "Item '$id' not found");
        }
        // Normalise fields that the legacy web UI stored in non-standard formats:
        //  recurWeekday: integer 0-6  →  "Sun"-"Sat" string
        //  priority:     string label →  integer 1-5
        $todos = array_map(function(array $t): array {
            if (isset($t['recurWeekday']) && $t['recurWeekday'] !== null) {
                $t['recurWeekday'] = normalise_recur_weekday($t['recurWeekday']) ?? $t['recurWeekday'];
            }
            if (isset($t['priority'])) {
                $norm = normalise_priority($t['priority']);
                if ($norm !== null) $t['priority'] = $norm;
            }
            return $t;
        }, $todos);

        // List all (optionally filter ?done=0|1)
        if (isset($_GET['done'])) {
            $filterDone = filter_var($_GET['done'], FILTER_VALIDATE_BOOLEAN);
            $todos = array_values(array_filter($todos,
                fn($t) => (bool)($t['done'] ?? false) === $filterDone));
        }
        echo json_encode(array_values($todos), JSON_UNESCAPED_UNICODE);
        break;

    // ── POST — add one new item ───────────────────────────────────────────────
    case 'POST':
        $body   = read_body();
        $fields = validate_fields($body, true);  // requireText = true

        $item = array_merge($fields, [
            // Server-assigned, non-negotiable:
            'id'      => uniqid('td_', true),
            'created' => date('c'),
            'done'    => false,              // always false on creation
        ]);

        // Canonical field order for readability
        $ordered = [
            'id'          => $item['id'],
            'text'        => $item['text'],
            'done'        => false,
            'priority'    => $item['priority'],
            'created'     => $item['created'],
            'due'         => $item['due'],
            'notes'       => $item['notes'],
            'tags'        => $item['tags'],
            'recurWeekday'=> $item['recurWeekday'],
            'recurDay'    => $item['recurDay'],
        ];

        $todos   = todos_load($TODO_FILE);
        $todos[] = $ordered;
        todos_save($TODO_FILE, $todos);

        http_response_code(201);
        echo json_encode($ordered, JSON_UNESCAPED_UNICODE);
        break;

    // ── PATCH — update mutable fields ────────────────────────────────────────
    case 'PATCH':
        if ($id === '') json_err(400, 'Missing ?id= parameter');

        $todos  = todos_load($TODO_FILE);
        $found  = false;
        $body   = read_body();
        $fields = validate_fields($body, false);  // requireText = false (partial update)

        foreach ($todos as &$item) {
            if (($item['id'] ?? '') !== $id) continue;

            $originalCreated = $item['created'] ?? date('c');

            // Merge validated fields; server-assigned fields cannot be overwritten
            foreach ($fields as $k => $v) {
                $item[$k] = $v;
            }
            $item['id']      = $id;              // restore server-assigned
            $item['created'] = $originalCreated; // restore server-assigned

            $found  = true;
            $result = $item;
            break;
        }
        unset($item);

        if (!$found) json_err(404, "Item '$id' not found");

        todos_save($TODO_FILE, $todos);
        echo json_encode($result, JSON_UNESCAPED_UNICODE);
        break;

    // ── DELETE ───────────────────────────────────────────────────────────────
    case 'DELETE':
        if ($id === '') json_err(400, 'Missing ?id= parameter');

        $todos  = todos_load($TODO_FILE);
        $before = count($todos);
        $todos  = array_values(array_filter($todos,
            fn($t) => ($t['id'] ?? '') !== $id));

        if (count($todos) === $before) json_err(404, "Item '$id' not found");

        todos_save($TODO_FILE, $todos);
        echo json_encode(['deleted' => $id], JSON_UNESCAPED_UNICODE);
        break;

    default:
        json_err(405, 'Method not allowed');
}
