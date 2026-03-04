<?php
/**
 * darkroom-api.php — REST API for the Darkroom process journal.
 *
 * Auth: session (same login as the main dashboard).
 *
 * RESOURCES
 *   chemistry | chemistry_types
 *   paper
 *   negative  | negative_types
 *   exposure
 *   photo
 *
 * METHODS
 *   GET    ?res=X          → list all
 *   GET    ?res=X&id=N     → get one
 *   POST   ?res=X          → create (JSON body)
 *   DELETE ?res=X&id=N     → delete
 *
 * RESPONSE
 *   Success: { "ok": true,  "data": … }
 *   Error:   { "ok": false, "error": "…" }  + appropriate HTTP status
 */
declare(strict_types=1);

require __DIR__ . '/auth.php';
auth_require_api();

require __DIR__ . '/db.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$method = $_SERVER['REQUEST_METHOD'];
$res    = (string)($_GET['res'] ?? '');
$id     = isset($_GET['id']) && $_GET['id'] !== '' ? (int)$_GET['id'] : null;

$body = [];
if (in_array($method, ['POST', 'PUT', 'PATCH'], true)) {
    $raw  = (string)file_get_contents('php://input');
    $body = (array)(json_decode($raw, true) ?? []);
}

// ── Response helpers ──────────────────────────────────────────

function send_ok(mixed $data): never
{
    echo json_encode(['ok' => true, 'data' => $data],
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function send_err(string $msg, int $code = 400): never
{
    http_response_code($code);
    echo json_encode(['ok' => false, 'error' => $msg], JSON_UNESCAPED_UNICODE);
    exit;
}

function null_or_float(mixed $v): ?float
{
    return ($v !== null && $v !== '') ? (float)$v : null;
}

function null_or_int(mixed $v): ?int
{
    return ($v !== null && $v !== '') ? (int)$v : null;
}

function null_or_str(mixed $v): ?string
{
    $s = trim((string)($v ?? ''));
    return $s !== '' ? $s : null;
}

// ── Router ────────────────────────────────────────────────────

try {
    match ($res) {
        'chemistry_types' => handle_lookup($method, $id, $body, 'chemistry_types'),
        'negative_types'  => handle_lookup($method, $id, $body, 'negative_types'),
        'chemistry'       => handle_chemistry($method, $id, $body),
        'paper'           => handle_paper($method, $id, $body),
        'negative'        => handle_negative($method, $id, $body),
        'exposure'        => handle_exposure($method, $id, $body),
        'photo'           => handle_photo($method, $id, $body),
        default           => send_err('Unknown resource', 404),
    };
} catch (PDOException $e) {
    send_err('Database error: ' . $e->getMessage(), 500);
}

// ═══════════════════════════════════════════════════════════════
// HANDLERS
// ═══════════════════════════════════════════════════════════════

// ── Generic lookup tables (chemistry_types / negative_types) ──

function handle_lookup(string $method, ?int $id, array $body, string $table): never
{
    $pdo = db();
    // Only allow known table names (already guaranteed by router, but double-check)
    if (!in_array($table, ['chemistry_types', 'negative_types'], true)) {
        send_err('Forbidden', 403);
    }

    if ($method === 'GET') {
        $rows = $pdo->query("SELECT * FROM `$table` ORDER BY name")->fetchAll();
        send_ok($rows);
    }

    if ($method === 'POST') {
        $name = null_or_str($body['name'] ?? '');
        if (!$name) send_err('name required');
        $st = $pdo->prepare("INSERT INTO `$table` (name) VALUES (?)");
        $st->execute([$name]);
        send_ok(['id' => (int)$pdo->lastInsertId(), 'name' => $name]);
    }

    if ($method === 'DELETE' && $id) {
        $pdo->prepare("DELETE FROM `$table` WHERE id=?")->execute([$id]);
        send_ok(['deleted' => $id]);
    }

    send_err('Method not allowed', 405);
}

// ── Chemistry ─────────────────────────────────────────────────

function handle_chemistry(string $method, ?int $id, array $body): never
{
    $pdo = db();

    if ($method === 'GET') {
        if ($id) {
            $st = $pdo->prepare("
                SELECT c.*, ct.name AS type_name,
                  (SELECT GROUP_CONCAT(parent_id) FROM chemistry_lineage WHERE child_id  = c.id) AS created_from_ids,
                  (SELECT GROUP_CONCAT(child_id)  FROM chemistry_lineage WHERE parent_id = c.id) AS created_ids
                FROM chemistry c
                LEFT JOIN chemistry_types ct ON ct.id = c.type_id
                WHERE c.id = ?
            ");
            $st->execute([$id]);
            $row = $st->fetch();
            if (!$row) send_err('Not found', 404);
            send_ok($row);
        }

        $rows = $pdo->query("
            SELECT c.*, ct.name AS type_name
            FROM chemistry c
            LEFT JOIN chemistry_types ct ON ct.id = c.type_id
            ORDER BY c.date_created DESC, c.id DESC
        ")->fetchAll();
        send_ok($rows);
    }

    if ($method === 'POST') {
        if (empty($body['date_created'])) send_err('date_created required');

        $st = $pdo->prepare("
            INSERT INTO chemistry (date_created, type_id, percent_solution, notes)
            VALUES (?, ?, ?, ?)
        ");
        $st->execute([
            $body['date_created'],
            null_or_int($body['type_id'] ?? null),
            null_or_float($body['percent_solution'] ?? null),
            null_or_str($body['notes'] ?? null),
        ]);
        $newId = (int)$pdo->lastInsertId();

        // Link to parent chemistry (created from)
        if (!empty($body['created_from_id'])) {
            $pdo->prepare("
                INSERT IGNORE INTO chemistry_lineage (parent_id, child_id) VALUES (?, ?)
            ")->execute([(int)$body['created_from_id'], $newId]);
        }

        send_ok(['id' => $newId]);
    }

    if ($method === 'DELETE' && $id) {
        $pdo->prepare("DELETE FROM chemistry WHERE id=?")->execute([$id]);
        send_ok(['deleted' => $id]);
    }

    send_err('Method not allowed', 405);
}

// ── Paper ─────────────────────────────────────────────────────

function handle_paper(string $method, ?int $id, array $body): never
{
    $pdo = db();

    if ($method === 'GET') {
        $rows = $pdo->query("
            SELECT p.*,
              CONCAT_WS(' ', ct.name, c.date_created) AS treatment_label
            FROM paper p
            LEFT JOIN chemistry c  ON c.id  = p.treatment_chemistry_id
            LEFT JOIN chemistry_types ct ON ct.id = c.type_id
            ORDER BY p.id DESC
        ")->fetchAll();
        send_ok($rows);
    }

    if ($method === 'POST') {
        $st = $pdo->prepare("
            INSERT INTO paper (manufacturer, label, weight, hot_press, treatment_chemistry_id, notes)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $st->execute([
            null_or_str($body['manufacturer'] ?? null),
            null_or_str($body['label']        ?? null),
            null_or_float($body['weight']     ?? null),
            (int)(bool)($body['hot_press']    ?? false),
            null_or_int($body['treatment_chemistry_id'] ?? null),
            null_or_str($body['notes']        ?? null),
        ]);
        send_ok(['id' => (int)$pdo->lastInsertId()]);
    }

    if ($method === 'DELETE' && $id) {
        $pdo->prepare("DELETE FROM paper WHERE id=?")->execute([$id]);
        send_ok(['deleted' => $id]);
    }

    send_err('Method not allowed', 405);
}

// ── Negatives ─────────────────────────────────────────────────

function handle_negative(string $method, ?int $id, array $body): never
{
    $pdo = db();

    if ($method === 'GET') {
        $rows = $pdo->query("
            SELECT n.*, nt.name AS type_name
            FROM negative n
            LEFT JOIN negative_types nt ON nt.id = n.type_id
            ORDER BY n.date_created DESC, n.id DESC
        ")->fetchAll();
        send_ok($rows);
    }

    if ($method === 'POST') {
        if (empty($body['date_created'])) send_err('date_created required');
        $st = $pdo->prepare("
            INSERT INTO negative (date_created, type_id, settings_notes)
            VALUES (?, ?, ?)
        ");
        $st->execute([
            $body['date_created'],
            null_or_int($body['type_id']       ?? null),
            null_or_str($body['settings_notes'] ?? null),
        ]);
        send_ok(['id' => (int)$pdo->lastInsertId()]);
    }

    if ($method === 'DELETE' && $id) {
        $pdo->prepare("DELETE FROM negative WHERE id=?")->execute([$id]);
        send_ok(['deleted' => $id]);
    }

    send_err('Method not allowed', 405);
}

// ── Exposures ─────────────────────────────────────────────────

function handle_exposure(string $method, ?int $id, array $body): never
{
    $pdo = db();

    if ($method === 'GET') {
        $rows = $pdo->query("
            SELECT e.*,
              nt.name                  AS neg_type,
              n.date_created           AS neg_date
            FROM exposure e
            LEFT JOIN negative n  ON n.id  = e.negative_id
            LEFT JOIN negative_types nt ON nt.id = n.type_id
            ORDER BY e.date_exposed DESC, e.id DESC
        ")->fetchAll();

        // Attach exposure_times arrays
        $times = $pdo->query(
            "SELECT * FROM exposure_times ORDER BY exposure_id, sort_order"
        )->fetchAll();
        $timeMap = [];
        foreach ($times as $t) {
            $timeMap[(int)$t['exposure_id']][] = $t;
        }
        foreach ($rows as &$r) {
            $r['times'] = $timeMap[(int)$r['id']] ?? [];
        }
        send_ok($rows);
    }

    if ($method === 'POST') {
        if (empty($body['date_exposed'])) send_err('date_exposed required');

        $st = $pdo->prepare("
            INSERT INTO exposure
              (date_exposed, test_strip, paper_soak_time, paper_soak_temp,
               hot_develop_time, hot_develop_temp,
               cool_develop_time, cool_develop_temp,
               negative_id, notes)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $st->execute([
            $body['date_exposed'],
            (int)(bool)($body['test_strip']        ?? false),
            null_or_int($body['paper_soak_time']   ?? null),
            null_or_float($body['paper_soak_temp'] ?? null),
            null_or_int($body['hot_develop_time']  ?? null),
            null_or_float($body['hot_develop_temp'] ?? null),
            null_or_int($body['cool_develop_time'] ?? null),
            null_or_float($body['cool_develop_temp'] ?? null),
            null_or_int($body['negative_id']       ?? null),
            null_or_str($body['notes']             ?? null),
        ]);
        $newId = (int)$pdo->lastInsertId();

        // Insert multiple exposure times
        if (!empty($body['times']) && is_array($body['times'])) {
            $tSt = $pdo->prepare(
                "INSERT INTO exposure_times (exposure_id, duration_minutes, sort_order) VALUES (?,?,?)"
            );
            foreach ($body['times'] as $i => $t) {
                $dur = null_or_float($t['duration_minutes'] ?? null);
                if ($dur !== null && $dur > 0) {
                    $tSt->execute([$newId, $dur, $i]);
                }
            }
        }

        send_ok(['id' => $newId]);
    }

    if ($method === 'DELETE' && $id) {
        $pdo->prepare("DELETE FROM exposure WHERE id=?")->execute([$id]);
        send_ok(['deleted' => $id]);
    }

    send_err('Method not allowed', 405);
}

// ── Photos ────────────────────────────────────────────────────

function handle_photo(string $method, ?int $id, array $body): never
{
    $pdo = db();

    if ($method === 'GET') {
        $rows = $pdo->query("
            SELECT ph.*,
              gct.name                            AS gelatin_type,
              CONCAT_WS(' ', p.manufacturer, p.label) AS paper_label,
              e.date_exposed                      AS exposure_date
            FROM photo ph
            LEFT JOIN chemistry gc   ON gc.id  = ph.gelatin_chemistry_id
            LEFT JOIN chemistry_types gct ON gct.id = gc.type_id
            LEFT JOIN paper p        ON p.id   = ph.paper_id
            LEFT JOIN exposure e     ON e.id   = ph.exposure_id
            ORDER BY ph.id DESC
        ")->fetchAll();

        // Attach sensitizer rows
        $sens = $pdo->query("
            SELECT ps.*, ct.name AS chem_type, c.date_created AS chem_date
            FROM photo_sensitizer ps
            JOIN chemistry c ON c.id = ps.chemistry_id
            LEFT JOIN chemistry_types ct ON ct.id = c.type_id
            ORDER BY ps.photo_id, ps.sort_order
        ")->fetchAll();
        $sensMap = [];
        foreach ($sens as $s) {
            $sensMap[(int)$s['photo_id']][] = $s;
        }
        foreach ($rows as &$r) {
            $r['sensitizers'] = $sensMap[(int)$r['id']] ?? [];
        }
        send_ok($rows);
    }

    if ($method === 'POST') {
        $st = $pdo->prepare("
            INSERT INTO photo
              (gelatin_chemistry_id, paper_id, amount_used, photo_size,
               date_sensitized, date_exposed, exposure_id, notes)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $st->execute([
            null_or_int($body['gelatin_chemistry_id'] ?? null),
            null_or_int($body['paper_id']             ?? null),
            null_or_str($body['amount_used']          ?? null),
            null_or_str($body['photo_size']           ?? null),
            null_or_str($body['date_sensitized']      ?? null),
            null_or_str($body['date_exposed']         ?? null),
            null_or_int($body['exposure_id']          ?? null),
            null_or_str($body['notes']                ?? null),
        ]);
        $newId = (int)$pdo->lastInsertId();

        // Sensitizer layers
        if (!empty($body['sensitizers']) && is_array($body['sensitizers'])) {
            $sSt = $pdo->prepare("
                INSERT INTO photo_sensitizer (photo_id, chemistry_id, amount_used, sort_order)
                VALUES (?,?,?,?)
            ");
            foreach ($body['sensitizers'] as $i => $s) {
                $cid = null_or_int($s['chemistry_id'] ?? null);
                if ($cid) {
                    $sSt->execute([
                        $newId,
                        $cid,
                        null_or_str($s['amount_used'] ?? null),
                        $i,
                    ]);
                }
            }
        }

        send_ok(['id' => $newId]);
    }

    if ($method === 'DELETE' && $id) {
        $pdo->prepare("DELETE FROM photo WHERE id=?")->execute([$id]);
        send_ok(['deleted' => $id]);
    }

    send_err('Method not allowed', 405);
}
