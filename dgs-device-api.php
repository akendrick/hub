<?php
/**
 * dgs-device-api.php — Dragon Go Server game list + SGF for TRMNL.
 *
 * Returns flat, Liquid-friendly JSON:
 *   total           — total running games
 *   my_turn_count   — games where it is your move
 *   g0_id … g9_id          — game IDs (oldest = lowest gid first)
 *   g0_opp … g9_opp        — opponent DGS handle
 *   g0_col … g9_col        — your color: B or W
 *   g0_moves … g9_moves    — move number
 *   g0_yours … g9_yours    — 1 if it's your turn, 0 otherwise
 *   g0_size … g9_size      — board size (9/13/19)
 *   g0_time … g9_time      — your time remaining string
 *   board_gid              — game ID of board displayed (oldest by gid)
 *   board_opp              — opponent in board game
 *   board_col              — your color in board game
 *   board_moves            — move count of board game
 *   board_size             — board size of board game
 *   board_yours            — 1 if your turn in board game
 *   board_sgf              — full SGF text (requires DGS session)
 *   board_error            — "auth_required" if SGF could not be fetched
 *
 * DGS HTML row structure (show_games.php):
 *   td.Button           → game.php?gid=GID
 *   td.Image (empty)
 *   td.Sgf              → sgf link
 *   td.User             → opponent display name (first user link ≠ me)
 *   td.User             → opponent DGS HANDLE  (second user link ≠ me)
 *   td.Rating           → opponent rating
 *   td.Rating           → my rating (uid=MY_UID)
 *   td.Image            → <img title="[HANDLE] has COLOR, COLOR to move">
 *   td                  → game type  (Go)
 *   td                  → rules      (Japanese)
 *   td.Number           → board size (19)
 *   td.Number           → komi
 *   td.Number           → move number
 *   td                  → rated (Yes)
 *   td.Image (empty)
 *   td.Date             → start date
 *   td.Date             → last move date
 *   td.RemTime*         → MY time remaining
 *   td.RemTime*         → opponent time remaining
 *
 * Config: /config/dgs-config.json
 *   { "uid":"24738", "username":"Shrimphead",
 *     "password":"...", "device_key":"..." }
 *
 * Usage: GET /dgs-device-api.php?key=YOUR_KEY
 * Cache: 5 minutes  (data/dgs-cache.json)
 * Poll interval: 15 min minimum (DGS fair-use policy)
 */

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

// ── Config ───────────────────────────────────────────────────────────────────
$cfg = json_decode(
    @file_get_contents(__DIR__ . '/config/dgs-config.json') ?: '{}', true
) ?: [];

$MY_UID      = $cfg['uid']        ?? '24738';
$MY_NAME     = $cfg['username']   ?? 'Shrimphead';
$DGS_PASS    = $cfg['password']   ?? '';
$DEVICE_KEY  = $cfg['device_key'] ?? '';
$MAX_GAMES   = 10;
$CACHE_FILE  = __DIR__ . '/data/dgs-cache.json';
$CACHE_TTL   = 300; // 5 minutes
$COOKIE_FILE = sys_get_temp_dir() . '/dgs_session_' . md5($MY_NAME) . '.txt';

// ── Device-key guard (optional — skip if blank) ───────────────────────────────
if ($DEVICE_KEY !== '') {
    $supplied = $_GET['key'] ?? $_SERVER['HTTP_X_API_KEY'] ?? '';
    if (!hash_equals($DEVICE_KEY, $supplied)) {
        http_response_code(403);
        echo json_encode(['error' => 'forbidden']);
        exit;
    }
}

// ── Debug / flush mode (?flush=1 bypasses cache, ?debug=1 adds diagnostics) ──
$flush = isset($_GET['flush']) || isset($_GET['debug']);
$debug = isset($_GET['debug']);

// ── Cache hit ────────────────────────────────────────────────────────────────
if (!$flush && file_exists($CACHE_FILE) && (time() - filemtime($CACHE_FILE)) < $CACHE_TTL) {
    echo file_get_contents($CACHE_FILE);
    exit;
}

// ── cURL helper ──────────────────────────────────────────────────────────────
function dgs_get(string $url, string $cookieFile = ''): string {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_USERAGENT      => 'TRMNL-DGS-Plugin/2.0',
        CURLOPT_SSL_VERIFYPEER => true,
    ]);
    if ($cookieFile) {
        curl_setopt($ch, CURLOPT_COOKIEFILE,  $cookieFile);
        curl_setopt($ch, CURLOPT_COOKIEJAR,   $cookieFile);
    }
    $result = curl_exec($ch);
    curl_close($ch);
    return $result ?: '';
}

// ── DGS session login ─────────────────────────────────────────────────────────
// DGS login form fields: userid + passwd  (NOT login/password)
// Re-login if cookie file is missing or older than 1 hour.
if ($DGS_PASS !== '') {
    $loginAge = file_exists($COOKIE_FILE) ? (time() - filemtime($COOKIE_FILE)) : PHP_INT_MAX;
    if ($loginAge > 3600) {
        $ch = curl_init('https://www.dragongoserver.net/login.php');
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => http_build_query([
                'userid'  => $MY_NAME,
                'passwd'  => $DGS_PASS,
                'login'   => 'Log in',
            ]),
            CURLOPT_COOKIEJAR      => $COOKIE_FILE,
            CURLOPT_COOKIEFILE     => $COOKIE_FILE,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_USERAGENT      => 'TRMNL-DGS-Plugin/2.0',
            CURLOPT_TIMEOUT        => 15,
        ]);
        curl_exec($ch);
        curl_close($ch);
    }
}
$ck = ($DGS_PASS !== '') ? $COOKIE_FILE : '';

// ── Fetch + parse show_games.php ─────────────────────────────────────────────
$html = dgs_get(
    'https://www.dragongoserver.net/show_games.php?uid=' . $MY_UID,
    $ck
);

$games = [];

if ($html && strpos($html, 'game.php?gid=') !== false) {

    $dom = new DOMDocument();
    libxml_use_internal_errors(true);
    $dom->loadHTML('<?xml encoding="UTF-8">' . $html);
    libxml_clear_errors();
    $xp = new DOMXPath($dom);

    // Each row that contains a game.php?gid link
    $rows = $xp->query('//tr[.//a[contains(@href,"game.php?gid=")]]');

    foreach ($rows as $row) {

        // ── Game ID ──────────────────────────────────────────────────────────
        $gLinks = $xp->query('.//a[contains(@href,"game.php?gid=")]', $row);
        if (!$gLinks->length) continue;
        preg_match('/gid=(\d+)/', $gLinks->item(0)->getAttribute('href'), $gm);
        $gid = $gm[1] ?? '';
        if (!$gid) continue;

        // ── Opponent handle ───────────────────────────────────────────────────
        // Rows have two userinfo links for the SAME opponent uid (display name + handle).
        // Shrimphead's uid appears only in ratinggraph links, not userinfo.
        // We want the SECOND userinfo link text (the DGS handle).
        $uLinks = $xp->query('.//a[contains(@href,"userinfo.php")]', $row);
        $opp = '?';
        if ($uLinks->length >= 2) {
            $opp = trim($uLinks->item(1)->textContent); // second = handle
        } elseif ($uLinks->length === 1) {
            $opp = trim($uLinks->item(0)->textContent);
        }

        // ── Color + whose turn ────────────────────────────────────────────────
        // Parsed from the status image title:
        //   "[HANDLE] has White, White to move"  → opponent White, White's turn
        //   "[HANDLE] has Black, Black to move"  → opponent Black, Black's turn
        $imgNodes = $xp->query('.//img[contains(@title," has ") and contains(@title," to move")]', $row);
        $myColor = '?';
        $yours   = 0;
        if ($imgNodes->length) {
            $title = $imgNodes->item(0)->getAttribute('title');
            // Extract: [HANDLE] has (Black|White), (Black|White) to move
            if (preg_match('/\[([^\]]+)\] has (Black|White), (Black|White) to move/i', $title, $cm)) {
                $oppColor    = strtoupper($cm[2][0]); // B or W (opponent's color)
                $movingColor = strtoupper($cm[3][0]); // B or W (who moves next)
                $myColor     = ($oppColor === 'B') ? 'W' : 'B';
                $yours       = ($movingColor === $myColor) ? 1 : 0;
            }
        }

        // ── All td cells (used for positional lookups) ───────────────────────
        $allCells = $xp->query('.//td', $row);

        // ── Board size + move count ───────────────────────────────────────────
        // td.Number cells in order: boardsize (idx 0), komi (idx 1), moves (idx 2)
        $numCells = $xp->query('.//td[@class="Number"]', $row);
        $size  = 19;
        $moves = 0;
        if ($numCells->length >= 1) $size  = (int)trim($numCells->item(0)->textContent);
        if ($numCells->length >= 3) $moves = (int)trim($numCells->item(2)->textContent);

        // ── Time remaining (my clock) ─────────────────────────────────────────
        // Column 17 (0-based) = my remaining time.
        // DGS only adds RemTime* CSS class when time is critical — otherwise
        // it's a plain <td>, so we must use position rather than class.
        $timeLeft = '';
        if ($allCells->length > 17) {
            $timeLeft = trim($allCells->item(17)->textContent);
        }
        // Fallback: scan for RemTime* class if column count differs
        if ($timeLeft === '') {
            $rtCells  = $xp->query('.//td[contains(@class,"RemTime")]', $row);
            if ($rtCells->length) $timeLeft = trim($rtCells->item(0)->textContent);
        }

        $games[(int)$gid] = [
            'gid'   => $gid,
            'opp'   => $opp,
            'color' => $myColor,
            'moves' => $moves,
            'yours' => $yours,
            'size'  => ($size > 0) ? $size : 19,
            'time'  => $timeLeft,
        ];
    }
}

// Sort by game ID ascending (oldest first)
ksort($games, SORT_NUMERIC);
$gameList = array_values($games);

// ── Build output ─────────────────────────────────────────────────────────────
$out = [
    'total'          => count($gameList),
    'my_turn_count'  => count(array_filter($gameList, fn($g) => $g['yours'] === 1)),
];

$cap = min(count($gameList), $MAX_GAMES);
for ($i = 0; $i < $cap; $i++) {
    $g = $gameList[$i];
    $out["g{$i}_id"]    = $g['gid'];
    $out["g{$i}_opp"]   = $g['opp'];
    $out["g{$i}_col"]   = $g['color'];
    $out["g{$i}_moves"] = (string)$g['moves'];
    $out["g{$i}_yours"] = $g['yours'];
    $out["g{$i}_size"]  = $g['size'];
    $out["g{$i}_time"]  = $g['time'];
}

// ── Fetch SGF for up to 6 oldest games ───────────────────────────────────────
// Stored as g0_sgf … g5_sgf alongside the existing g0_* metadata variables.
// The TRMNL markup uses time-based JS rotation to choose which games to render
// as boards each refresh cycle.
$sgfCount = min(6, count($gameList));
for ($si = 0; $si < $sgfCount; $si++) {
    $bg  = $gameList[$si];
    $sgf = dgs_get(
        'https://www.dragongoserver.net/sgf.php?gid=' . $bg['gid'],
        $ck
    );
    $out["g{$si}_sgf"] = ($sgf && strpos($sgf, 'GM[') !== false) ? $sgf : '';
}

// ── Debug diagnostics (only when ?debug=1) ───────────────────────────────────
if ($debug) {
    $cfgPath    = __DIR__ . '/config/dgs-config.json';
    $cfgExists  = file_exists($cfgPath);
    $cookieAge  = file_exists($COOKIE_FILE) ? (time() - filemtime($COOKIE_FILE)) . 's ago' : 'missing';
    $cacheAge   = file_exists($CACHE_FILE)  ? (time() - filemtime($CACHE_FILE))  . 's ago' : 'missing';
    $out['_debug'] = [
        'config_file'  => $cfgExists ? 'found' : 'MISSING — deploy config/dgs-config.json',
        'config_uid'   => $MY_UID,
        'config_user'  => $MY_NAME,
        'has_password' => $DGS_PASS !== '' ? 'yes' : 'NO — add password to config',
        'cookie_file'  => $cookieAge,
        'cache_file'   => $cacheAge,
        'html_length'  => strlen($html),
        'rows_found'   => count($games),
    ];
}

// ── Cache + emit ─────────────────────────────────────────────────────────────
$json = json_encode($out, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
@mkdir(dirname($CACHE_FILE), 0755, true);
if (!$debug) @file_put_contents($CACHE_FILE, $json); // don't cache debug responses
echo $json;
