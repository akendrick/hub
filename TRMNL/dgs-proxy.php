<?php
/**
 * dgs-proxy.php — Dragon Go Server → JSON for TRMNL
 *
 * Fetches the public quick_status.php feed for a DGS user (no auth required),
 * parses the custom pipe-delimited text format, and returns clean JSON.
 *
 * Usage:  GET /dgs-proxy.php?key=YOUR_DEVICE_KEY
 *
 * Returns:
 * {
 *   "user":        "shrimphead",
 *   "my_turn":     [ { gid, opponent, color, size, handicap, moves, time_left, url }, … ],
 *   "their_turn":  [ { gid, opponent, color, size, handicap, moves, time_left, url }, … ],
 *   "total":       N,
 *   "my_turn_count": N,
 *   "fetched":     "2026-05-14T10:30:00-07:00"
 * }
 *
 * ── Setup ─────────────────────────────────────────────────────────────────────
 * 1. Set DEVICE_KEY and DGS_USER below.
 * 2. Drop this file next to kaslo-weather.php on knotwork.ca.
 * ─────────────────────────────────────────────────────────────────────────────
 */

// ── Config ────────────────────────────────────────────────────────────────────
const DEVICE_KEY = 'REPLACE_WITH_YOUR_SECRET_KEY';
const DGS_USER   = 'shrimphead';
const DGS_URL    = 'https://www.dragongoserver.net/quick_status.php?user=' . DGS_USER;
const GAME_BASE  = 'https://www.dragongoserver.net/game.php?gid=';
const FETCH_TIMEOUT = 10;

// ── Headers ───────────────────────────────────────────────────────────────────
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate');

// ── Auth ──────────────────────────────────────────────────────────────────────
$supplied = trim($_GET['key'] ?? '');
if ($supplied === '' || !hash_equals(DEVICE_KEY, $supplied)) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// ── Fetch ─────────────────────────────────────────────────────────────────────
$ctx = stream_context_create(['http' => [
    'timeout'       => FETCH_TIMEOUT,
    'user_agent'    => 'Mozilla/5.0 (compatible; TRMNL-dgs-proxy/1.0)',
    'ignore_errors' => true,
]]);

$raw = @file_get_contents(DGS_URL, false, $ctx);

if ($raw === false || trim($raw) === '') {
    http_response_code(502);
    echo json_encode(['error' => 'Failed to fetch DGS status']);
    exit;
}

// ── Parser ────────────────────────────────────────────────────────────────────
/**
 * The DGS quick_status format for the public (not-logged-in) GAMESTATUS section:
 *
 *   #*GAMESTATUS
 *   <version line — skip>
 *   <one line per game, pipe-separated>
 *
 * Each game line has these fields (as documented in the DGS quick_suite spec
 * and confirmed by DGSLib/DragonGoApp source analysis):
 *
 *   gid | opponent | color | size | handicap | komi | num_moves | status |
 *   time_remaining | last_move (GMT) | [ optional extra fields ]
 *
 * color  : 'B' (you play black) or 'W' (you play white)
 * status : 'play' = normal move, 'pass' = pass is legal, 'score' = scoring,
 *          'move' = it's your turn broadly, or 'opp' = waiting for opponent
 *
 * The public feed returns ALL running games, not only those awaiting your move.
 * We determine whose turn it is from the status field and the to_move marker.
 *
 * NOTE: DGS occasionally changes minor format details. The parser is intentionally
 * lenient — it skips lines it doesn't recognise rather than crashing.
 */
function parse_quick_status(string $raw): array {
    $my_turn    = [];
    $their_turn = [];

    $lines = preg_split('/\r?\n/', $raw);
    $in_gamestatus = false;

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '') continue;

        // Section header
        if ($line === '#*GAMESTATUS') {
            $in_gamestatus = true;
            continue;
        }
        // Another section starts — stop reading games
        if (str_starts_with($line, '#') && $in_gamestatus && $line !== '#*GAMESTATUS') {
            // Check if it's a new section (e.g. #*BULLETIN, #*MESSAGE)
            if (preg_match('/^#\*[A-Z]/', $line)) {
                $in_gamestatus = false;
            }
            continue;
        }
        if (!$in_gamestatus) continue;
        // Skip version/comment lines
        if (str_starts_with($line, '#')) continue;

        $fields = explode('|', $line);
        if (count($fields) < 6) continue;

        // Field positions (0-indexed):
        // 0=gid, 1=opponent, 2=color, 3=size, 4=handicap, 5=komi,
        // 6=moves, 7=status, 8=time_remaining, 9=last_move_time
        $gid      = (int) trim($fields[0]);
        $opponent = trim($fields[1] ?? '');
        $color    = strtoupper(trim($fields[2] ?? 'B')); // 'B' or 'W'
        $size     = (int) trim($fields[3] ?? 19);
        $handicap = (int) trim($fields[4] ?? 0);
        $komi     = trim($fields[5] ?? '6.5');
        $moves    = (int) trim($fields[6] ?? 0);
        $status   = strtolower(trim($fields[7] ?? ''));
        $time_left = trim($fields[8] ?? '');
        $last_move = trim($fields[9] ?? '');

        if ($gid <= 0 || $opponent === '') continue;

        $game = [
            'gid'       => $gid,
            'opponent'  => $opponent,
            'color'     => $color,          // 'B' or 'W'
            'size'      => $size,
            'handicap'  => $handicap,
            'komi'      => $komi,
            'moves'     => $moves,
            'time_left' => $time_left,
            'last_move' => $last_move,
            'url'       => GAME_BASE . $gid,
        ];

        // 'play', 'pass', 'score', 'move' = your turn
        // 'opp', 'wait', 'opponent' = their turn
        // Anything else treated as their turn
        $is_my_turn = in_array($status, ['play', 'pass', 'score', 'move', 'your_turn'], true)
            || str_starts_with($status, 'play')
            || str_starts_with($status, 'move');

        if ($is_my_turn) {
            $my_turn[] = $game;
        } else {
            $their_turn[] = $game;
        }
    }

    return [$my_turn, $their_turn];
}

[$my_turn, $their_turn] = parse_quick_status($raw);

// ── Output ────────────────────────────────────────────────────────────────────
$tz = new DateTimeZone('America/Vancouver');
echo json_encode([
    'user'          => DGS_USER,
    'my_turn'       => $my_turn,
    'their_turn'    => $their_turn,
    'total'         => count($my_turn) + count($their_turn),
    'my_turn_count' => count($my_turn),
    'fetched'       => (new DateTime('now', $tz))->format(DateTime::ATOM),
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
