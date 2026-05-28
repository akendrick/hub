<?php
/**
 * go-board-proxy.php — Dragon Go Server board position for TRMNL go.* plugins
 *
 * Fetches DGS quick_status for shrimphead, picks game N from the active list
 * (my-turn games first, then their-turn), downloads its SGF, and replays all
 * moves (with captures) to return the current 19×19 board position as JSON.
 *
 * Usage:  GET /go-board-proxy.php?key=YOUR_DEVICE_KEY&game=0
 *         game=0 → first active game  (used by go.todo)
 *         game=1 → second active game (used by go.cal)
 *         game=2 → third active game  (used by go.weather)
 *
 * Returns:
 * {
 *   "opponent":         "username",
 *   "color":            "B",          // shrimphead's stone colour
 *   "moves":            45,
 *   "time_left":        "3d 5h",
 *   "my_turn":          true,
 *   "my_turn_count":    3,
 *   "total_games":      7,
 *   "game_index":       0,
 *   "last_col":         10,           // -1 if no moves yet
 *   "last_row":         10,
 *   "last_color":       "B",
 *   "board_black_json": "[[col,row],…]",
 *   "board_white_json": "[[col,row],…]"
 * }
 *
 * ── Setup ─────────────────────────────────────────────────────────────────────
 * 1. Set DEVICE_KEY below.
 * 2. Drop next to dgs-proxy.php on knotwork.ca.
 * 3. Point go.todo at ?game=0, go.cal at ?game=1, go.weather at ?game=2.
 * ─────────────────────────────────────────────────────────────────────────────
 */

const DEVICE_KEY     = 'REPLACE_WITH_YOUR_SECRET_KEY';
const DGS_USER       = 'shrimphead';
const DGS_STATUS_URL = 'https://www.dragongoserver.net/quick_status.php?user=' . DGS_USER;
const DGS_SGF_BASE   = 'https://www.dragongoserver.net/sgf.php?gid=';
const FETCH_TIMEOUT  = 12;

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate');

$supplied = trim($_GET['key'] ?? '');
if ($supplied === '' || !hash_equals(DEVICE_KEY, $supplied)) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$game_index = max(0, (int) ($_GET['game'] ?? 0));

function fetch_url(string $url): ?string {
    $ctx = stream_context_create(['http' => [
        'timeout'       => FETCH_TIMEOUT,
        'user_agent'    => 'Mozilla/5.0 (compatible; TRMNL-go-proxy/1.0)',
        'ignore_errors' => true,
    ]]);
    $r = @file_get_contents($url, false, $ctx);
    return ($r !== false && trim($r) !== '') ? $r : null;
}

function parse_quick_status(string $raw): array {
    $my_turn = [];
    $their_turn = [];
    $in_gs = false;

    foreach (preg_split('/\r?\n/', $raw) as $line) {
        $line = trim($line);
        if ($line === '') continue;
        if ($line === '#*GAMESTATUS')              { $in_gs = true; continue; }
        if (preg_match('/^#\*[A-Z]/', $line) && $in_gs) { $in_gs = false; continue; }
        if (!$in_gs || str_starts_with($line, '#')) continue;

        $f = explode('|', $line);
        if (count($f) < 8) continue;

        $gid  = (int) trim($f[0]);
        $opp  = trim($f[1] ?? '');
        $size = (int) trim($f[3] ?? 19);
        if ($gid <= 0 || $opp === '' || $size !== 19) continue; // 19×19 only

        $g = [
            'gid'       => $gid,
            'opponent'  => $opp,
            'color'     => strtoupper(trim($f[2] ?? 'B')),
            'moves'     => (int) trim($f[6] ?? 0),
            'time_left' => trim($f[8] ?? ''),
        ];

        $status  = strtolower(trim($f[7] ?? ''));
        $is_mine = in_array($status, ['play', 'pass', 'score', 'move', 'your_turn'], true)
            || str_starts_with($status, 'play')
            || str_starts_with($status, 'move');

        ($is_mine ? $my_turn : $their_turn)[] = $g;
    }

    return [$my_turn, $their_turn];
}

// ── Go board: group + liberty flood-fill ──────────────────────────────────────
function get_group(array &$board, int $r, int $c, string $color): array {
    $visited = [];
    $queue   = [[$r, $c]];
    $libs    = 0;
    $stones  = [];

    while (!empty($queue)) {
        [$cr, $cc] = array_pop($queue);
        $k = $cr * 19 + $cc;
        if (isset($visited[$k])) continue;
        $visited[$k] = true;
        $stones[] = [$cr, $cc];

        foreach ([[-1,0],[1,0],[0,-1],[0,1]] as [$dr, $dc]) {
            $nr = $cr + $dr;
            $nc = $cc + $dc;
            if ($nr < 0 || $nr >= 19 || $nc < 0 || $nc >= 19) continue;
            $nk = $nr * 19 + $nc;
            if ($board[$nr][$nc] === '')              $libs++;
            elseif ($board[$nr][$nc] === $color && !isset($visited[$nk]))
                $queue[] = [$nr, $nc];
        }
    }

    return ['stones' => $stones, 'liberties' => $libs];
}

function remove_if_captured(array &$board, int $r, int $c, string $color): void {
    $g = get_group($board, $r, $c, $color);
    if ($g['liberties'] === 0)
        foreach ($g['stones'] as [$sr, $sc])
            $board[$sr][$sc] = '';
}

// ── SGF replay → current board position ──────────────────────────────────────
function replay_sgf(string $sgf): array {
    $board = [];
    for ($r = 0; $r < 19; $r++) $board[$r] = array_fill(0, 19, '');

    $last_col   = -1;
    $last_row   = -1;
    $last_color = '';

    // Setup stones AB[xy] AW[xy]
    preg_match_all('/A([BW])\[([a-s]{2})\]/i', $sgf, $setup, PREG_SET_ORDER);
    foreach ($setup as $s) {
        $color = strtoupper($s[1]);
        $c = ord($s[2][0]) - 97;
        $r = ord($s[2][1]) - 97;
        if ($c >= 0 && $c < 19 && $r >= 0 && $r < 19)
            $board[$r][$c] = $color;
    }

    // Move sequence: ;B[xy] ;W[xy], pass = empty coord
    preg_match_all('/;([BW])\[([a-s]{0,2})\]/', $sgf, $moves, PREG_SET_ORDER);
    foreach ($moves as $mv) {
        $color = $mv[1];
        $coord = $mv[2];
        if (strlen($coord) < 2) continue; // pass

        $c = ord($coord[0]) - 97;
        $r = ord($coord[1]) - 97;
        if ($c < 0 || $c >= 19 || $r < 0 || $r >= 19) continue;
        if ($board[$r][$c] !== '') continue;

        $board[$r][$c] = $color;
        $last_col   = $c;
        $last_row   = $r;
        $last_color = $color;

        $opp = $color === 'B' ? 'W' : 'B';
        foreach ([[-1,0],[1,0],[0,-1],[0,1]] as [$dr, $dc]) {
            $nr = $r + $dr;
            $nc = $c + $dc;
            if ($nr >= 0 && $nr < 19 && $nc >= 0 && $nc < 19 && $board[$nr][$nc] === $opp)
                remove_if_captured($board, $nr, $nc, $opp);
        }
        remove_if_captured($board, $r, $c, $color); // suicide guard
    }

    $black = [];
    $white = [];
    for ($r = 0; $r < 19; $r++)
        for ($c = 0; $c < 19; $c++) {
            if ($board[$r][$c] === 'B') $black[] = [$c, $r];
            elseif ($board[$r][$c] === 'W') $white[] = [$c, $r];
        }

    return [
        'last_col'         => $last_col,
        'last_row'         => $last_row,
        'last_color'       => $last_color,
        'board_black_json' => json_encode($black, JSON_UNESCAPED_SLASHES),
        'board_white_json' => json_encode($white, JSON_UNESCAPED_SLASHES),
    ];
}

// ── Main ──────────────────────────────────────────────────────────────────────
$raw_status = fetch_url(DGS_STATUS_URL);
if (!$raw_status) {
    http_response_code(502);
    echo json_encode(['error' => 'DGS unavailable']);
    exit;
}

[$my_turn, $their_turn] = parse_quick_status($raw_status);

// My-turn games first, then their-turn; pick game N
$all = array_values(array_merge($my_turn, $their_turn));
$chosen = $all[$game_index] ?? $all[0] ?? null;
$is_my_turn = $chosen ? in_array($chosen, $my_turn, true) : false;

$board_data = [
    'last_col'         => -1,
    'last_row'         => -1,
    'last_color'       => '',
    'board_black_json' => '[]',
    'board_white_json' => '[]',
];

if ($chosen) {
    $sgf = fetch_url(DGS_SGF_BASE . $chosen['gid']);
    if ($sgf) $board_data = replay_sgf($sgf);
}

echo json_encode(array_merge($board_data, [
    'opponent'      => $chosen['opponent'] ?? '',
    'color'         => $chosen['color']    ?? '',
    'moves'         => $chosen['moves']    ?? 0,
    'time_left'     => $chosen['time_left'] ?? '',
    'my_turn'       => $is_my_turn,
    'my_turn_count' => count($my_turn),
    'total_games'   => count($all),
    'game_index'    => $game_index,
]), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
