<?php
/**
 * darkroom-device-api.php — iOS / remote device REST API for the Darkroom journal.
 *
 * AUTHENTICATION
 *   Same device API key system used by device-api.php (weather/todos).
 *   Every request must carry a valid per-device key issued via api-keys.php.
 *
 *     Authorization: Bearer kw_<64 hex chars>
 *   OR
 *     ?api_key=kw_<64 hex chars>        (HTTPS only — for quick testing)
 *
 * ─────────────────────────────────────────────────────────────────────────────
 * RESOURCES & ENDPOINTS
 * ─────────────────────────────────────────────────────────────────────────────
 *
 *  LOOKUP TABLES  (read + add items; used to drive iOS picker lists)
 *  ─────────────
 *  GET  ?res=chemistry_types              → list all chemistry types
 *  POST ?res=chemistry_types              → add new type  { "name": "…" }
 *  GET  ?res=negative_types               → list all negative types
 *  POST ?res=negative_types               → add new type  { "name": "…" }
 *
 *  CHEMISTRY
 *  ─────────
 *  GET    ?res=chemistry                  → list all (newest first)
 *  GET    ?res=chemistry&id=N             → single record + lineage
 *  POST   ?res=chemistry                  → create
 *         Body: {
 *           "date_created":     "YYYY-MM-DD",   // required
 *           "type_id":          1,               // int|null
 *           "percent_solution": 5.0,             // float|null
 *           "created_from_id":  3,               // parent chemistry id|null
 *           "notes":            "…"              // string|null
 *         }
 *  PATCH  ?res=chemistry&id=N             → update any subset of the same fields
 *  DELETE ?res=chemistry&id=N             → delete record
 *
 *  PAPER
 *  ─────
 *  GET    ?res=paper                      → list all
 *  GET    ?res=paper&id=N                 → single record
 *  POST   ?res=paper                      → create
 *         Body: {
 *           "manufacturer":           "…",      // string|null
 *           "label":                  "…",      // string|null
 *           "weight":                 300.0,    // float|null (gsm)
 *           "hot_press":              true,     // bool
 *           "treatment_chemistry_id": 2,        // int|null
 *           "notes":                  "…"       // string|null
 *         }
 *  PATCH  ?res=paper&id=N                 → update any subset
 *  DELETE ?res=paper&id=N                 → delete record
 *
 *  NEGATIVES
 *  ─────────
 *  GET    ?res=negative                   → list all
 *  GET    ?res=negative&id=N              → single record
 *  POST   ?res=negative                   → create
 *         Body: {
 *           "date_created":   "YYYY-MM-DD",     // required
 *           "type_id":        1,                // int|null
 *           "settings_notes": "…"              // string|null
 *         }
 *  PATCH  ?res=negative&id=N              → update any subset
 *  DELETE ?res=negative&id=N             → delete record
 *
 *  EXPOSURES
 *  ─────────
 *  GET    ?res=exposure                   → list all (with nested times array)
 *  GET    ?res=exposure&id=N              → single record + times
 *  POST   ?res=exposure                   → create
 *         Body: {
 *           "date_exposed":      "YYYY-MM-DD",  // required
 *           "test_strip":        false,          // bool
 *           "negative_id":       4,             // int|null
 *           "paper_soak_time":   5,             // int minutes|null
 *           "paper_soak_temp":   20.0,          // float °C|null
 *           "hot_develop_time":  90,            // int seconds|null
 *           "hot_develop_temp":  38.0,          // float °C|null
 *           "cool_develop_time": 60,            // int seconds|null
 *           "cool_develop_temp": 20.0,          // float °C|null
 *           "notes":             "…",           // string|null
 *           "times": [                          // array of intervals
 *             { "duration_minutes": 10 },
 *             { "duration_minutes": 10 },
 *             { "duration_minutes": 15 }
 *           ]
 *         }
 *  PATCH  ?res=exposure&id=N              → update scalar fields (times not patched; use POST)
 *  DELETE ?res=exposure&id=N             → delete record + its times
 *
 *  PHOTOS
 *  ──────
 *  GET    ?res=photo                      → list all (with nested sensitizers array)
 *  GET    ?res=photo&id=N                 → single record + sensitizers
 *  POST   ?res=photo                      → create
 *         Body: {
 *           "paper_id":             2,          // int|null
 *           "photo_size":           "8x10",     // string|null
 *           "gelatin_chemistry_id": 1,          // int|null
 *           "amount_used":          "20ml",     // string|null (gelatin amount)
 *           "date_sensitized":      "YYYY-MM-DD",
 *           "date_exposed":         "YYYY-MM-DD",
 *           "exposure_id":          3,          // int|null
 *           "notes":                "…",        // string|null
 *           "sensitizers": [                    // ordered sensitizer layers
 *             { "chemistry_id": 5, "amount_used": "5ml" },
 *             { "chemistry_id": 6, "amount_used": "3ml" }
 *           ]
 *         }
 *  PATCH  ?res=photo&id=N                 → update scalar fields (sensitizers not patched)
 *  DELETE ?res=photo&id=N                → delete record + its sensitizers
 *
 * ─────────────────────────────────────────────────────────────────────────────
 * RESPONSE FORMAT
 * ─────────────────────────────────────────────────────────────────────────────
 *   Success:  HTTP 200   { "ok": true,  "data": <object|array> }
 *   Created:  HTTP 201   { "ok": true,  "data": { "id": N } }
 *   Error:    HTTP 4xx   { "ok": false, "error": "human-readable message" }
 *   DB error: HTTP 500   { "ok": false, "error": "Database error: …" }
 *
 * ─────────────────────────────────────────────────────────────────────────────
 * UNITS  (all metric, same as weather-api.php)
 * ─────────────────────────────────────────────────────────────────────────────
 *   temperature : °C
 *   weight      : gsm
 *   time        : minutes (soak) / seconds (develop) / minutes (exposure)
 */

declare(strict_types=1);

require __DIR__ . '/auth.php';
auth_require_device_api();   // 401 if key missing or invalid

require __DIR__ . '/db.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

// ── CORS (uncomment & restrict origin for production) ─────────────────────────
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Authorization, Content-Type');
header('Access-Control-Allow-Methods: GET, POST, PATCH, DELETE, OPTIONS');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

$method = $_SERVER['REQUEST_METHOD'];
$res    = (string)($_GET['res'] ?? '');
$id     = isset($_GET['id']) && $_GET['id'] !== '' ? (int)$_GET['id'] : null;

$body = [];
if (in_array($method, ['POST', 'PUT', 'PATCH'], true)) {
    $raw  = (string)file_get_contents('php://input');
    $body = (array)(json_decode($raw, true) ?? []);
}

// ── Response helpers ──────────────────────────────────────────────────────────

function ok(mixed $data, int $code = 200): never
{
    http_response_code($code);
    echo json_encode(['ok' => true, 'data' => $data],
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    exit;
}

function err(string $msg, int $code = 400): never
{
    http_response_code($code);
    echo json_encode(['ok' => false, 'error' => $msg], JSON_UNESCAPED_UNICODE);
    exit;
}

function nf(?float $v): ?float  { return ($v !== null && $v !== '') ? (float)$v : null; }
function ni(?mixed $v): ?int    { return ($v !== null && $v !== '') ? (int)$v  : null; }
function ns(?mixed $v): ?string { $s = trim((string)($v ?? '')); return $s !== '' ? $s : null; }
function nb(mixed  $v): int     { return (int)(bool)$v; }

// ── Router ────────────────────────────────────────────────────────────────────

try {
    match ($res) {
        'chemistry_types' => lookup($method, $id, $body, 'chemistry_types'),
        'negative_types'  => lookup($method, $id, $body, 'negative_types'),
        'chemistry'       => chemistry($method, $id, $body),
        'paper'           => paper($method, $id, $body),
        'negative'        => negative($method, $id, $body),
        'exposure'        => exposure($method, $id, $body),
        'photo'           => photo($method, $id, $body),
        default           => err('Unknown resource. Valid: chemistry_types, negative_types, chemistry, paper, negative, exposure, photo', 404),
    };
} catch (PDOException $e) {
    err('Database error: ' . $e->getMessage(), 500);
}

// ═════════════════════════════════════════════════════════════════════════════
// HANDLERS
// ═════════════════════════════════════════════════════════════════════════════

// ── Lookup tables ─────────────────────────────────────────────────────────────

function lookup(string $m, ?int $id, array $body, string $table): never
{
    if (!in_array($table, ['chemistry_types', 'negative_types'], true)) err('Forbidden', 403);
    $pdo = db();

    if ($m === 'GET') {
        ok($pdo->query("SELECT * FROM `$table` ORDER BY name")->fetchAll());
    }
    if ($m === 'POST') {
        $name = ns($body['name'] ?? null) ?? err('name required');
        $pdo->prepare("INSERT INTO `$table` (name) VALUES (?)")->execute([$name]);
        ok(['id' => (int)$pdo->lastInsertId(), 'name' => $name], 201);
    }
    if ($m === 'DELETE' && $id) {
        $pdo->prepare("DELETE FROM `$table` WHERE id=?")->execute([$id]);
        ok(['deleted' => $id]);
    }
    err('Method not allowed', 405);
}

// ── Chemistry ─────────────────────────────────────────────────────────────────

function chemistry(string $m, ?int $id, array $body): never
{
    $pdo = db();

    // GET list
    if ($m === 'GET' && !$id) {
        $rows = $pdo->query("
            SELECT c.*, ct.name AS type_name,
              (SELECT GROUP_CONCAT(parent_id ORDER BY parent_id)
               FROM chemistry_lineage WHERE child_id  = c.id) AS created_from_ids,
              (SELECT GROUP_CONCAT(child_id  ORDER BY child_id)
               FROM chemistry_lineage WHERE parent_id = c.id) AS created_ids
            FROM chemistry c
            LEFT JOIN chemistry_types ct ON ct.id = c.type_id
            ORDER BY c.date_created DESC, c.id DESC
        ")->fetchAll();
        ok($rows);
    }

    // GET single
    if ($m === 'GET' && $id) {
        $st = $pdo->prepare("
            SELECT c.*, ct.name AS type_name,
              (SELECT GROUP_CONCAT(parent_id ORDER BY parent_id)
               FROM chemistry_lineage WHERE child_id  = c.id) AS created_from_ids,
              (SELECT GROUP_CONCAT(child_id  ORDER BY child_id)
               FROM chemistry_lineage WHERE parent_id = c.id) AS created_ids
            FROM chemistry c
            LEFT JOIN chemistry_types ct ON ct.id = c.type_id
            WHERE c.id = ?
        ");
        $st->execute([$id]);
        $row = $st->fetch() ?: err('Not found', 404);
        ok($row);
    }

    // POST create
    if ($m === 'POST') {
        empty($body['date_created']) && err('date_created required');
        $pdo->prepare("
            INSERT INTO chemistry (date_created, type_id, percent_solution, notes)
            VALUES (?, ?, ?, ?)
        ")->execute([
            $body['date_created'],
            ni($body['type_id']          ?? null),
            nf($body['percent_solution'] ?? null),
            ns($body['notes']            ?? null),
        ]);
        $newId = (int)$pdo->lastInsertId();
        if (!empty($body['created_from_id'])) {
            $pdo->prepare(
                "INSERT IGNORE INTO chemistry_lineage (parent_id, child_id) VALUES (?,?)"
            )->execute([(int)$body['created_from_id'], $newId]);
        }
        ok(['id' => $newId], 201);
    }

    // PATCH update
    if ($m === 'PATCH' && $id) {
        $allowed = ['date_created','type_id','percent_solution','notes'];
        $sets = []; $vals = [];
        foreach ($allowed as $f) {
            if (!array_key_exists($f, $body)) continue;
            $sets[] = "`$f` = ?";
            $vals[] = match($f) {
                'type_id'          => ni($body[$f]),
                'percent_solution' => nf($body[$f]),
                default            => ns($body[$f]),
            };
        }
        if ($sets) {
            $vals[] = $id;
            $pdo->prepare("UPDATE chemistry SET " . implode(',', $sets) . " WHERE id=?")->execute($vals);
        }
        if (array_key_exists('created_from_id', $body)) {
            $pdo->prepare("DELETE FROM chemistry_lineage WHERE child_id=?")->execute([$id]);
            if ($body['created_from_id']) {
                $pdo->prepare(
                    "INSERT IGNORE INTO chemistry_lineage (parent_id, child_id) VALUES (?,?)"
                )->execute([(int)$body['created_from_id'], $id]);
            }
        }
        ok(['updated' => $id]);
    }

    // DELETE
    if ($m === 'DELETE' && $id) {
        $pdo->prepare("DELETE FROM chemistry WHERE id=?")->execute([$id]);
        ok(['deleted' => $id]);
    }

    err('Method not allowed', 405);
}

// ── Paper ─────────────────────────────────────────────────────────────────────

function paper(string $m, ?int $id, array $body): never
{
    $pdo = db();

    if ($m === 'GET' && !$id) {
        ok($pdo->query("
            SELECT p.*, ct.name AS type_name,
              CONCAT_WS(' ', c.date_created, ct.name) AS treatment_label
            FROM paper p
            LEFT JOIN chemistry c    ON c.id  = p.treatment_chemistry_id
            LEFT JOIN chemistry_types ct ON ct.id = c.type_id
            ORDER BY p.id DESC
        ")->fetchAll());
    }

    if ($m === 'GET' && $id) {
        $st = $pdo->prepare("SELECT p.*,
          CONCAT_WS(' ', c.date_created, ct.name) AS treatment_label
          FROM paper p
          LEFT JOIN chemistry c    ON c.id  = p.treatment_chemistry_id
          LEFT JOIN chemistry_types ct ON ct.id = c.type_id
          WHERE p.id = ?");
        $st->execute([$id]);
        ok($st->fetch() ?: err('Not found', 404));
    }

    if ($m === 'POST') {
        $pdo->prepare("
            INSERT INTO paper (manufacturer, label, weight, hot_press, treatment_chemistry_id, notes)
            VALUES (?, ?, ?, ?, ?, ?)
        ")->execute([
            ns($body['manufacturer']           ?? null),
            ns($body['label']                  ?? null),
            nf($body['weight']                 ?? null),
            nb($body['hot_press']              ?? false),
            ni($body['treatment_chemistry_id'] ?? null),
            ns($body['notes']                  ?? null),
        ]);
        ok(['id' => (int)$pdo->lastInsertId()], 201);
    }

    if ($m === 'PATCH' && $id) {
        $allowed = ['manufacturer','label','weight','hot_press','treatment_chemistry_id','notes'];
        $sets = []; $vals = [];
        foreach ($allowed as $f) {
            if (!array_key_exists($f, $body)) continue;
            $sets[] = "`$f` = ?";
            $vals[] = match($f) {
                'weight'                 => nf($body[$f]),
                'treatment_chemistry_id' => ni($body[$f]),
                'hot_press'              => nb($body[$f]),
                default                  => ns($body[$f]),
            };
        }
        if ($sets) { $vals[] = $id; $pdo->prepare("UPDATE paper SET " . implode(',', $sets) . " WHERE id=?")->execute($vals); }
        ok(['updated' => $id]);
    }

    if ($m === 'DELETE' && $id) {
        $pdo->prepare("DELETE FROM paper WHERE id=?")->execute([$id]);
        ok(['deleted' => $id]);
    }

    err('Method not allowed', 405);
}

// ── Negatives ─────────────────────────────────────────────────────────────────

function negative(string $m, ?int $id, array $body): never
{
    $pdo = db();

    if ($m === 'GET' && !$id) {
        ok($pdo->query("
            SELECT n.*, nt.name AS type_name
            FROM negative n
            LEFT JOIN negative_types nt ON nt.id = n.type_id
            ORDER BY n.date_created DESC, n.id DESC
        ")->fetchAll());
    }

    if ($m === 'GET' && $id) {
        $st = $pdo->prepare("SELECT n.*, nt.name AS type_name
          FROM negative n LEFT JOIN negative_types nt ON nt.id = n.type_id WHERE n.id=?");
        $st->execute([$id]);
        ok($st->fetch() ?: err('Not found', 404));
    }

    if ($m === 'POST') {
        empty($body['date_created']) && err('date_created required');
        $pdo->prepare("
            INSERT INTO negative (date_created, type_id, settings_notes)
            VALUES (?, ?, ?)
        ")->execute([
            $body['date_created'],
            ni($body['type_id']        ?? null),
            ns($body['settings_notes'] ?? null),
        ]);
        ok(['id' => (int)$pdo->lastInsertId()], 201);
    }

    if ($m === 'PATCH' && $id) {
        $allowed = ['date_created','type_id','settings_notes'];
        $sets = []; $vals = [];
        foreach ($allowed as $f) {
            if (!array_key_exists($f, $body)) continue;
            $sets[] = "`$f` = ?";
            $vals[] = $f === 'type_id' ? ni($body[$f]) : ns($body[$f]);
        }
        if ($sets) { $vals[] = $id; $pdo->prepare("UPDATE negative SET " . implode(',', $sets) . " WHERE id=?")->execute($vals); }
        ok(['updated' => $id]);
    }

    if ($m === 'DELETE' && $id) {
        $pdo->prepare("DELETE FROM negative WHERE id=?")->execute([$id]);
        ok(['deleted' => $id]);
    }

    err('Method not allowed', 405);
}

// ── Exposures ─────────────────────────────────────────────────────────────────

function exposure(string $m, ?int $id, array $body): never
{
    $pdo = db();

    $attachTimes = function(array &$rows) use ($pdo): void {
        if (!$rows) return;
        $ids = implode(',', array_map(fn($r) => (int)$r['id'], $rows));
        $times = $pdo->query(
            "SELECT * FROM exposure_times WHERE exposure_id IN ($ids) ORDER BY exposure_id, sort_order"
        )->fetchAll();
        $map = [];
        foreach ($times as $t) $map[(int)$t['exposure_id']][] = $t;
        foreach ($rows as &$r) $r['times'] = $map[(int)$r['id']] ?? [];
    };

    if ($m === 'GET' && !$id) {
        $rows = $pdo->query("
            SELECT e.*, nt.name AS neg_type, n.date_created AS neg_date
            FROM exposure e
            LEFT JOIN negative n  ON n.id  = e.negative_id
            LEFT JOIN negative_types nt ON nt.id = n.type_id
            ORDER BY e.date_exposed DESC, e.id DESC
        ")->fetchAll();
        $attachTimes($rows);
        ok($rows);
    }

    if ($m === 'GET' && $id) {
        $st = $pdo->prepare("
            SELECT e.*, nt.name AS neg_type, n.date_created AS neg_date
            FROM exposure e
            LEFT JOIN negative n  ON n.id  = e.negative_id
            LEFT JOIN negative_types nt ON nt.id = n.type_id
            WHERE e.id = ?");
        $st->execute([$id]);
        $row = $st->fetch() ?: err('Not found', 404);
        $rows = [$row];
        $attachTimes($rows);
        ok($rows[0]);
    }

    if ($m === 'POST') {
        empty($body['date_exposed']) && err('date_exposed required');
        $pdo->prepare("
            INSERT INTO exposure
              (date_exposed, test_strip, paper_soak_time, paper_soak_temp,
               hot_develop_time, hot_develop_temp,
               cool_develop_time, cool_develop_temp,
               negative_id, notes)
            VALUES (?,?,?,?,?,?,?,?,?,?)
        ")->execute([
            $body['date_exposed'],
            nb($body['test_strip']         ?? false),
            ni($body['paper_soak_time']    ?? null),
            nf($body['paper_soak_temp']    ?? null),
            ni($body['hot_develop_time']   ?? null),
            nf($body['hot_develop_temp']   ?? null),
            ni($body['cool_develop_time']  ?? null),
            nf($body['cool_develop_temp']  ?? null),
            ni($body['negative_id']        ?? null),
            ns($body['notes']              ?? null),
        ]);
        $newId = (int)$pdo->lastInsertId();

        if (!empty($body['times']) && is_array($body['times'])) {
            $tst = $pdo->prepare(
                "INSERT INTO exposure_times (exposure_id, duration_minutes, sort_order) VALUES (?,?,?)"
            );
            foreach ($body['times'] as $i => $t) {
                $dur = nf($t['duration_minutes'] ?? null);
                if ($dur !== null && $dur > 0) $tst->execute([$newId, $dur, $i]);
            }
        }
        ok(['id' => $newId], 201);
    }

    if ($m === 'PATCH' && $id) {
        $allowed = ['date_exposed','test_strip','paper_soak_time','paper_soak_temp',
                    'hot_develop_time','hot_develop_temp','cool_develop_time','cool_develop_temp',
                    'negative_id','notes'];
        $sets = []; $vals = [];
        foreach ($allowed as $f) {
            if (!array_key_exists($f, $body)) continue;
            $sets[] = "`$f` = ?";
            $vals[] = match($f) {
                'test_strip'                           => nb($body[$f]),
                'paper_soak_time','hot_develop_time',
                'cool_develop_time','negative_id'      => ni($body[$f]),
                'paper_soak_temp','hot_develop_temp',
                'cool_develop_temp'                    => nf($body[$f]),
                default                                => ns($body[$f]),
            };
        }
        if ($sets) { $vals[] = $id; $pdo->prepare("UPDATE exposure SET " . implode(',', $sets) . " WHERE id=?")->execute($vals); }
        ok(['updated' => $id]);
    }

    if ($m === 'DELETE' && $id) {
        $pdo->prepare("DELETE FROM exposure WHERE id=?")->execute([$id]);
        ok(['deleted' => $id]);
    }

    err('Method not allowed', 405);
}

// ── Photos ────────────────────────────────────────────────────────────────────

function photo(string $m, ?int $id, array $body): never
{
    $pdo = db();

    $attachSens = function(array &$rows) use ($pdo): void {
        if (!$rows) return;
        $ids = implode(',', array_map(fn($r) => (int)$r['id'], $rows));
        $sens = $pdo->query("
            SELECT ps.*, ct.name AS chem_type, c.date_created AS chem_date
            FROM photo_sensitizer ps
            JOIN chemistry c ON c.id = ps.chemistry_id
            LEFT JOIN chemistry_types ct ON ct.id = c.type_id
            WHERE ps.photo_id IN ($ids)
            ORDER BY ps.photo_id, ps.sort_order
        ")->fetchAll();
        $map = [];
        foreach ($sens as $s) $map[(int)$s['photo_id']][] = $s;
        foreach ($rows as &$r) $r['sensitizers'] = $map[(int)$r['id']] ?? [];
    };

    if ($m === 'GET' && !$id) {
        $rows = $pdo->query("
            SELECT ph.*,
              gct.name                               AS gelatin_type,
              CONCAT_WS(' ', p.manufacturer, p.label) AS paper_label,
              e.date_exposed                         AS exposure_date
            FROM photo ph
            LEFT JOIN chemistry gc   ON gc.id  = ph.gelatin_chemistry_id
            LEFT JOIN chemistry_types gct ON gct.id = gc.type_id
            LEFT JOIN paper p        ON p.id   = ph.paper_id
            LEFT JOIN exposure e     ON e.id   = ph.exposure_id
            ORDER BY ph.id DESC
        ")->fetchAll();
        $attachSens($rows);
        ok($rows);
    }

    if ($m === 'GET' && $id) {
        $st = $pdo->prepare("
            SELECT ph.*,
              gct.name                               AS gelatin_type,
              CONCAT_WS(' ', p.manufacturer, p.label) AS paper_label,
              e.date_exposed                         AS exposure_date
            FROM photo ph
            LEFT JOIN chemistry gc   ON gc.id  = ph.gelatin_chemistry_id
            LEFT JOIN chemistry_types gct ON gct.id = gc.type_id
            LEFT JOIN paper p        ON p.id   = ph.paper_id
            LEFT JOIN exposure e     ON e.id   = ph.exposure_id
            WHERE ph.id = ?");
        $st->execute([$id]);
        $row = $st->fetch() ?: err('Not found', 404);
        $rows = [$row];
        $attachSens($rows);
        ok($rows[0]);
    }

    if ($m === 'POST') {
        $pdo->prepare("
            INSERT INTO photo
              (gelatin_chemistry_id, paper_id, amount_used, photo_size,
               date_sensitized, date_exposed, exposure_id, notes)
            VALUES (?,?,?,?,?,?,?,?)
        ")->execute([
            ni($body['gelatin_chemistry_id'] ?? null),
            ni($body['paper_id']             ?? null),
            ns($body['amount_used']          ?? null),
            ns($body['photo_size']           ?? null),
            ns($body['date_sensitized']      ?? null),
            ns($body['date_exposed']         ?? null),
            ni($body['exposure_id']          ?? null),
            ns($body['notes']                ?? null),
        ]);
        $newId = (int)$pdo->lastInsertId();

        if (!empty($body['sensitizers']) && is_array($body['sensitizers'])) {
            $sst = $pdo->prepare("
                INSERT INTO photo_sensitizer (photo_id, chemistry_id, amount_used, sort_order)
                VALUES (?,?,?,?)
            ");
            foreach ($body['sensitizers'] as $i => $s) {
                $cid = ni($s['chemistry_id'] ?? null);
                if ($cid) $sst->execute([$newId, $cid, ns($s['amount_used'] ?? null), $i]);
            }
        }
        ok(['id' => $newId], 201);
    }

    if ($m === 'PATCH' && $id) {
        $allowed = ['gelatin_chemistry_id','paper_id','amount_used','photo_size',
                    'date_sensitized','date_exposed','exposure_id','notes'];
        $sets = []; $vals = [];
        foreach ($allowed as $f) {
            if (!array_key_exists($f, $body)) continue;
            $sets[] = "`$f` = ?";
            $vals[] = match($f) {
                'gelatin_chemistry_id','paper_id','exposure_id' => ni($body[$f]),
                default                                          => ns($body[$f]),
            };
        }
        if ($sets) { $vals[] = $id; $pdo->prepare("UPDATE photo SET " . implode(',', $sets) . " WHERE id=?")->execute($vals); }
        ok(['updated' => $id]);
    }

    if ($m === 'DELETE' && $id) {
        $pdo->prepare("DELETE FROM photo WHERE id=?")->execute([$id]);
        ok(['deleted' => $id]);
    }

    err('Method not allowed', 405);
}
