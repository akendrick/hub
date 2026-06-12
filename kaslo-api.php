<?php
/**
 * kaslo-api.php — Unified TRMNL data endpoint for Kaslo BC home dashboard
 *
 * Replaces: go-board-proxy.php, todo-device-api.php, cal-device-api.php
 *           + direct EcoWitt and Open-Meteo polling URLs
 *
 * GET /kaslo-api.php?key=SECRET[&game=N]
 *   game=0  go.todo   (default when omitted: skip DGS/SGF fetch)
 *   game=1  go.cal
 *   game=2  go.weather
 *
 * Response keys:
 *   .go    DGS board position + game meta  (only when ?game=N provided)
 *   .wx    EcoWitt current conditions (flat scalars, pre-rounded)
 *   .fc    Open-Meteo forecast + pressure history + moon
 *   .sun   { rise, set }  — today's sunrise/sunset HH:MM
 *   .moon  { phase, name }
 *   .todo  { urgent[], rest[], total }
 *   .cal   { days[] }  each day has events_timed[], events_allday[], holiday
 */

// ── Config ────────────────────────────────────────────────────────────────────
const DEVICE_KEY    = 'kw_40e818911c1980bcd56dc4aff37a820f811990a130eb771fd9e21a536edc55ea';
const DGS_USER      = 'shrimphead';
const TODOS_FILE    = __DIR__ . '/data/todos.json';
const CACHE_DIR     = '/tmp';
const TZ            = 'America/Vancouver';
const FETCH_TIMEOUT = 10;
const CAL_DAYS      = 14;

// EcoWitt v3 API — real-time conditions
const ECOWITT_URL =
    'https://api.ecowitt.net/api/v3/device/real_time'
    . '?application_key=C6FD389063D6A82CC7A68532000A5962'
    . '&api_key=1c2c26a5-a293-4f82-a69a-9e77b6447344'
    . '&mac=E0:5A:1B:21:11:57'
    . '&call_back=all'
    . '&temp_unitid=1&pressure_unitid=3&wind_speed_unitid=7&rainfall_unitid=12';

// Open-Meteo — 3 days past hourly pressure + 7-day forecast + moon phase
const OPEN_METEO_URL =
    'https://api.open-meteo.com/v1/forecast?latitude=49.912&longitude=-116.908'
    . '&current=weather_code'
    . '&daily=temperature_2m_max,temperature_2m_min,precipitation_sum,'
    .   'precipitation_probability_max,weather_code,sunrise,sunset'
    . '&hourly=surface_pressure'
    . '&wind_speed_unit=kmh&timezone=America%2FVancouver&forecast_days=7&past_days=3';

const DGS_STATUS_URL = 'https://www.dragongoserver.net/quick_status.php?user=' . DGS_USER;
const DGS_SGF_BASE   = 'https://www.dragongoserver.net/sgf.php?gid=';

const ICAL_FEEDS = [
    'https://p162-caldav.icloud.com/published/2/MTI5MzgzNTk0MTI5MzgzNWY9cTYgK0OwIRz4UCfQYKvJY44bQNHC73gDwS5V1U5jUkV9ynvy7uipCHeNIfenut1Eq0LNCaulcS6IDwnCXzpP_iBTsC0Fc21d3SMFtSvB_1CnVBymPyAyPnzFyGkhsg',
    'https://p102-caldav.icloud.com/published/2/MTI5MzgzNTk0MTI5MzgzNWY9cTYgK0OwIRz4UCfQYKvnN6NvIE36DmOUbrBgiLcGN7ezhsYo-YXFxAw38AN_vpaiEFRiXefJROr9Az80VcU',
    'https://p101-caldav.icloud.com/published/2/Mjc4Mjk1ODMxMjc4Mjk1OPiLHZnPp67Ltgtp3v229x8qT-uPdlC-Sg6bv_JZdLUiimpxJVvfu-OL9CBtnZ3CMevVIgwICabIi9WTyZIKqHA',
];

const BC_HOLIDAYS = [
    '2026-01-01' => "New Year's Day",   '2026-02-16' => 'Family Day',
    '2026-04-03' => 'Good Friday',      '2026-05-18' => 'Victoria Day',
    '2026-07-01' => 'Canada Day',       '2026-08-03' => 'British Columbia Day',
    '2026-09-07' => 'Labour Day',       '2026-09-30' => 'Truth & Reconciliation Day',
    '2026-10-12' => 'Thanksgiving',     '2026-11-11' => 'Remembrance Day',
    '2026-12-25' => 'Christmas Day',
];

// Cache TTLs (seconds)
const TTL_WX   = 300;    // 5 min  — weather station
const TTL_FC   = 1800;   // 30 min — forecast
const TTL_DGS  = 900;    // 15 min — game list + board
const TTL_ICAL = 1800;   // 30 min — calendars

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
$game_param = isset($_GET['game']) ? max(0, (int)$_GET['game']) : null;

// ── Cache helpers ─────────────────────────────────────────────────────────────
function cache_get(string $name, int $ttl): ?array {
    $path = CACHE_DIR . '/kaslo_' . $name . '.json';
    if (!file_exists($path) || time() - filemtime($path) >= $ttl) return null;
    $d = json_decode(file_get_contents($path), true);
    return is_array($d) ? $d : null;
}
function cache_set(string $name, array $data): void {
    file_put_contents(CACHE_DIR . '/kaslo_' . $name . '.json', json_encode($data), LOCK_EX);
}

// ── HTTP ──────────────────────────────────────────────────────────────────────
function http_get(string $url): ?string {
    $ctx = stream_context_create(['http' => [
        'timeout'       => FETCH_TIMEOUT,
        'user_agent'    => 'Mozilla/5.0 (compatible; kaslo-api/2.0)',
        'ignore_errors' => true,
    ]]);
    $r = @file_get_contents($url, false, $ctx);
    return ($r !== false && trim($r) !== '') ? $r : null;
}

// ── Lookup helpers ────────────────────────────────────────────────────────────
function wmo_label(int $c): string {
    if ($c===0)  return 'Clear';
    if ($c===1)  return 'Mostly Clear';
    if ($c===2)  return 'Partly Cloudy';
    if ($c===3)  return 'Overcast';
    if ($c===45||$c===48) return 'Fog';
    if ($c>=51&&$c<=55)   return 'Drizzle';
    if ($c>=61&&$c<=65)   return 'Rain';
    if ($c===66||$c===67) return 'Freezing Rain';
    if ($c>=71&&$c<=75)   return 'Snow';
    if ($c>=80&&$c<=82)   return 'Showers';
    if ($c===85||$c===86) return 'Snow Showers';
    if ($c>=95)           return 'Thunderstorm';
    return '—';
}
function wind_dir(float $d): string {
    return ['N','NE','E','SE','S','SW','W','NW'][(int)round($d / 45) % 8];
}
function moon_phase_name(float $p): string {
    $n = ['New Moon','Waxing Crescent','First Quarter','Waxing Gibbous',
          'Full Moon','Waning Gibbous','Last Quarter','Waning Crescent'];
    return $n[(int)round($p * 8) % 8];
}

// ── EcoWitt ───────────────────────────────────────────────────────────────────
function ecowitt_val(array $data, string ...$path): string {
    $node = $data;
    foreach ($path as $k) {
        if (!is_array($node) || !array_key_exists($k, $node)) return '';
        $node = $node[$k];
    }
    return is_array($node) ? (string)($node['value'] ?? '') : (string)$node;
}

function build_wx(): array {
    $c = cache_get('wx', TTL_WX);
    if ($c) return $c;

    $raw = http_get(ECOWITT_URL);
    if (!$raw) return ['error' => 'fetch_failed'];

    $d = json_decode($raw, true);
    $o = $d['data'] ?? [];

    $wd = (float)ecowitt_val($o, 'wind', 'wind_direction', 'value');

    $result = [
        'temp'        => (string)(int)floatval(ecowitt_val($o, 'outdoor',      'temperature',   'value')),
        'temp_full'   => ecowitt_val($o, 'outdoor', 'temperature', 'value'),
        'feels'       => (string)(int)floatval(ecowitt_val($o, 'outdoor',      'feels_like',    'value')),
        'rain_day'    => ecowitt_val($o, 'rainfall_piezo', 'daily',     'value'),
        'rain_rate'   => ecowitt_val($o, 'rainfall_piezo', 'rain_rate', 'value'),
        'rain_week'   => ecowitt_val($o, 'rainfall_piezo', 'weekly',    'value'),
        'wind_kmh'    => (string)(int)floatval(ecowitt_val($o, 'wind',         'wind_speed',    'value')),
        'gust_kmh'    => (string)(int)floatval(ecowitt_val($o, 'wind',         'wind_gust',     'value')),
        'wdir'        => wind_dir($wd),
        'humidity'    => ecowitt_val($o, 'outdoor',      'humidity',      'value'),
        'dew'         => (string)(int)floatval(ecowitt_val($o, 'outdoor',      'dew_point',     'value')),
        'pressure'    => ecowitt_val($o, 'pressure',     'relative',      'value'),
        'uvi'         => ecowitt_val($o, 'solar_and_uvi','uvi',           'value'),
        'solar'       => (string)(int)floatval(ecowitt_val($o, 'solar_and_uvi','solar',         'value')),
        'indoor_temp' => (string)(int)floatval(ecowitt_val($o, 'indoor',       'temperature',   'value')),
        'indoor_hum'  => ecowitt_val($o, 'indoor',       'humidity',      'value'),
    ];

    cache_set('wx', $result);
    return $result;
}

// ── Open-Meteo forecast ───────────────────────────────────────────────────────
function build_fc(): array {
    $c = cache_get('forecast', TTL_FC);
    if ($c) return $c;

    $raw = http_get(OPEN_METEO_URL);
    if (!$raw) return ['error' => 'fetch_failed'];

    $d = json_decode($raw, true);
    if (!is_array($d)) return ['error' => 'parse_error'];

    $tz  = new DateTimeZone(TZ);
    $now = new DateTime('now', $tz);

    // 7-day forecast days
    $days = [];
    for ($i = 0; $i < 7; $i++) {
        $code   = (int)($d['daily']['weather_code'][$i] ?? 0);
        $sr_raw = $d['daily']['sunrise'][$i] ?? '';
        $ss_raw = $d['daily']['sunset'][$i]  ?? '';
        $days[] = [
            'date'      => $d['daily']['time'][$i] ?? '',
            'dow'       => (new DateTime($d['daily']['time'][$i] ?? 'today', $tz))->format('D'),
            'hi'        => (int)round($d['daily']['temperature_2m_max'][$i]          ?? 0),
            'lo'        => (int)round($d['daily']['temperature_2m_min'][$i]          ?? 0),
            'code'      => $code,
            'condition' => wmo_label($code),
            'pop'       => (int)($d['daily']['precipitation_probability_max'][$i]    ?? 0),
            'mm'        => round((float)($d['daily']['precipitation_sum'][$i]        ?? 0), 1),
            'sunrise'   => substr(explode('T', $sr_raw)[1] ?? '', 0, 5),
            'sunset'    => substr(explode('T', $ss_raw)[1] ?? '', 0, 5),
        ];
    }

    // Pressure history — past 3 days up to current hour
    $all_p   = $d['hourly']['surface_pressure'] ?? [];
    $cur_idx = min(72 + (int)$now->format('G'), count($all_p) - 1);
    $history = array_slice($all_p, 0, $cur_idx + 1);
    $cur_p   = (float)(end($history) ?: 0);
    $prev_p  = (float)($history[max(0, count($history) - 4)] ?? $cur_p);
    $delta   = $cur_p - $prev_p;

    // Moon phase (computed — Open-Meteo no longer provides moon_phase)
    $synodic = 29.530588853;
    $known_new_moon = new DateTime('2000-01-06 18:14:00', new DateTimeZone('UTC'));
    $days_since = ($now->getTimestamp() - $known_new_moon->getTimestamp()) / 86400;
    $moon_phase = fmod($days_since, $synodic) / $synodic;
    if ($moon_phase < 0) $moon_phase += 1;

    // Current condition from Open-Meteo current
    $cur_code = (int)($d['current']['weather_code'] ?? 0);

    $result = [
        'days'                  => $days,
        'current_code'          => $cur_code,
        'current_condition'     => wmo_label($cur_code),
        'moon_phase'            => $moon_phase,
        'moon_name'             => moon_phase_name($moon_phase),
        'pressure_now'          => round($cur_p, 1),
        'pressure_trend'        => $delta > 0.5 ? '↑ rising' : ($delta < -0.5 ? '↓ falling' : '→ steady'),
        'pressure_history_json' => json_encode(array_values($history), JSON_UNESCAPED_SLASHES),
    ];

    cache_set('forecast', $result);
    return $result;
}

// ── DGS board (only when ?game= is supplied) ──────────────────────────────────
function parse_quick_status(string $raw): array {
    $my = []; $their = []; $in_gs = false;
    foreach (preg_split('/\r?\n/', $raw) as $line) {
        $line = trim($line);
        if ($line === '') continue;
        if ($line === '#*GAMESTATUS') { $in_gs = true; continue; }
        if (preg_match('/^#\*[A-Z]/', $line) && $in_gs) { $in_gs = false; continue; }
        if (!$in_gs || str_starts_with($line, '#')) continue;
        $f = explode('|', $line);
        if (count($f) < 8) continue;
        $gid = (int)trim($f[0]); $opp = trim($f[1] ?? ''); $size = (int)trim($f[3] ?? 19);
        if ($gid <= 0 || $opp === '' || $size !== 19) continue;
        $g = ['gid' => $gid, 'opponent' => $opp, 'color' => strtoupper(trim($f[2] ?? 'B')),
              'moves' => (int)trim($f[6] ?? 0), 'time_left' => trim($f[8] ?? '')];
        $s = strtolower(trim($f[7] ?? ''));
        $mine = in_array($s, ['play','pass','score','move','your_turn'], true)
            || str_starts_with($s, 'play') || str_starts_with($s, 'move');
        if ($mine) { $my[] = $g; } else { $their[] = $g; }
    }
    return [$my, $their];
}

function get_group(array &$board, int $r, int $c, string $color): array {
    $vis = []; $q = [[$r,$c]]; $libs = 0; $stones = [];
    while (!empty($q)) {
        [$cr,$cc] = array_pop($q); $k = $cr * 19 + $cc;
        if (isset($vis[$k])) continue;
        $vis[$k] = true; $stones[] = [$cr,$cc];
        foreach ([[-1,0],[1,0],[0,-1],[0,1]] as [$dr,$dc]) {
            $nr = $cr+$dr; $nc = $cc+$dc;
            if ($nr<0||$nr>=19||$nc<0||$nc>=19) continue;
            $nk = $nr*19+$nc;
            if ($board[$nr][$nc]==='') $libs++;
            elseif ($board[$nr][$nc]===$color&&!isset($vis[$nk])) $q[]=[$nr,$nc];
        }
    }
    return ['stones'=>$stones,'liberties'=>$libs];
}
function remove_if_captured(array &$board, int $r, int $c, string $color): void {
    $g = get_group($board,$r,$c,$color);
    if ($g['liberties']===0) foreach ($g['stones'] as [$sr,$sc]) $board[$sr][$sc]='';
}

function replay_sgf(string $sgf): array {
    $board = []; for ($r=0;$r<19;$r++) $board[$r]=array_fill(0,19,'');
    $lc=-1; $lr=-1; $lcol='';
    preg_match_all('/A([BW])\[([a-s]{2})\]/i',$sgf,$setup,PREG_SET_ORDER);
    foreach ($setup as $s) {
        $c=ord($s[2][0])-97; $r=ord($s[2][1])-97;
        if ($c>=0&&$c<19&&$r>=0&&$r<19) $board[$r][$c]=strtoupper($s[1]);
    }
    preg_match_all('/;([BW])\[([a-s]{0,2})\]/',$sgf,$moves,PREG_SET_ORDER);
    foreach ($moves as $mv) {
        $color=$mv[1]; $coord=$mv[2]; if (strlen($coord)<2) continue;
        $c=ord($coord[0])-97; $r=ord($coord[1])-97;
        if ($c<0||$c>=19||$r<0||$r>=19||$board[$r][$c]!=='') continue;
        $board[$r][$c]=$color; $lc=$c; $lr=$r; $lcol=$color;
        $opp=$color==='B'?'W':'B';
        foreach ([[-1,0],[1,0],[0,-1],[0,1]] as [$dr,$dc]) {
            $nr=$r+$dr; $nc=$c+$dc;
            if ($nr>=0&&$nr<19&&$nc>=0&&$nc<19&&$board[$nr][$nc]===$opp)
                remove_if_captured($board,$nr,$nc,$opp);
        }
        remove_if_captured($board,$r,$c,$color);
    }
    $black=[]; $white=[];
    for ($r=0;$r<19;$r++) for ($c=0;$c<19;$c++) {
        if ($board[$r][$c]==='B') $black[]=[$c,$r];
        elseif ($board[$r][$c]==='W') $white[]=[$c,$r];
    }
    return ['last_col'=>$lc,'last_row'=>$lr,'last_color'=>$lcol,
            'board_black_json'=>json_encode($black,JSON_UNESCAPED_SLASHES),
            'board_white_json'=>json_encode($white,JSON_UNESCAPED_SLASHES)];
}

function build_go(int $game_idx): array {
    $list = cache_get('dgs_list', TTL_DGS);
    if (!$list) {
        $raw = http_get(DGS_STATUS_URL);
        if (!$raw) return ['error'=>'dgs_unavailable','board_black_json'=>'[]','board_white_json'=>'[]','last_col'=>-1,'last_row'=>-1,'last_color'=>'','opponent'=>'','color'=>'','moves'=>0,'time_left'=>'','my_turn'=>false,'my_turn_count'=>0,'total_games'=>0,'game_index'=>$game_idx];
        [$my,$their] = parse_quick_status($raw);
        $list = ['my'=>$my,'their'=>$their];
        cache_set('dgs_list',$list);
    }
    $all = array_values(array_merge($list['my'],$list['their']));
    $chosen = $all[$game_idx] ?? $all[0] ?? null;
    $is_mine = $chosen && in_array($chosen,$list['my'],true);

    $board = ['last_col'=>-1,'last_row'=>-1,'last_color'=>'','board_black_json'=>'[]','board_white_json'=>'[]'];
    if ($chosen) {
        $bkey = 'dgs_board_'.$chosen['gid'];
        $bc = cache_get($bkey, TTL_DGS);
        if (!$bc) {
            $sgf = http_get(DGS_SGF_BASE.$chosen['gid']);
            if ($sgf) { $bc = replay_sgf($sgf); cache_set($bkey,$bc); }
        }
        if ($bc) $board = $bc;
    }

    return array_merge($board,[
        'opponent'      => $chosen['opponent']  ?? '',
        'color'         => $chosen['color']     ?? '',
        'moves'         => $chosen['moves']     ?? 0,
        'time_left'     => $chosen['time_left'] ?? '',
        'my_turn'       => $is_mine,
        'my_turn_count' => count($list['my']),
        'total_games'   => count($all),
        'game_index'    => $game_idx,
    ]);
}

// ── Todos ─────────────────────────────────────────────────────────────────────
function build_todo(array $ical_todos = []): array {
    $all = [];
    if (file_exists(TODOS_FILE)&&is_readable(TODOS_FILE)) {
        $f = json_decode(file_get_contents(TODOS_FILE),true);
        if (is_array($f)) $all = $f;
    }
    $all = array_merge($all, $ical_todos);

    if (!$all) return ['urgent'=>[],'rest'=>[],'total'=>0];

    $today    = (new DateTime('today'))->format('Y-m-d');
    $tomorrow = (new DateTime('tomorrow'))->format('Y-m-d');
    $in3      = (new DateTime('+3 days'))->format('Y-m-d');

    $fmt = function(?string $due) use ($today,$tomorrow): string {
        if (empty($due)) return '';
        if ($due===$today)    return 'Today';
        if ($due===$tomorrow) return 'Tomorrow';
        $days=(int)(new DateTime('today'))->diff(new DateTime($due))->format('%r%a');
        if ($days<0) return abs($days).'d overdue';
        return (new DateTime($due))->format('M j');
    };

    $urgent=[]; $rest=[];
    foreach ($all as $item) {
        if (!empty($item['done'])) continue;
        if (($item['recurWeekday']??'')!==''||($item['recurDay']??'')!=='') continue;
        $pri=(int)($item['priority']??5); $due=$item['due']??'';
        $overdue=(!empty($due)&&$due<$today);
        $is_urgent=$overdue||$pri<=2||(!empty($due)&&$due<=$in3);
        $tags=(array)($item['tags']??[]);
        $due_days=!empty($due)?(int)(new DateTime('today'))->diff(new DateTime($due))->format('%r%a'):999999;
        $out=['text'=>$item['text']??'','priority'=>$pri,'due_label'=>$fmt($due?:null),
              'due_days'=>$due_days,'overdue'=>$overdue,
              'tags'=>array_values($tags),'tags_str'=>implode(' ',$tags)];
        if ($is_urgent) { $urgent[]=$out; } else { $rest[]=$out; }
    }
    usort($urgent,fn($a,$b)=>$a['due_days']-$b['due_days']?:$a['priority']-$b['priority']);
    usort($rest,  fn($a,$b)=>$a['priority']-$b['priority']?:strcmp($a['text'],$b['text']));

    return ['urgent'=>$urgent,'rest'=>$rest,'total'=>count($urgent)+count($rest)];
}

// ── Calendar ──────────────────────────────────────────────────────────────────
function ical_unfold(string $t): string {
    return preg_replace('/\r?\n[ \t]/','', $t);
}
function parse_dt(string $val): array {
    $val=preg_replace('/^[A-Z\/+_-]+:/','',trim($val));
    if (preg_match('/^(\d{4})(\d{2})(\d{2})$/',$val,$m))
        return ['date'=>"$m[1]-$m[2]-$m[3]",'time'=>null,'allday'=>true];
    if (preg_match('/^(\d{4})(\d{2})(\d{2})T(\d{2})(\d{2})/',$val,$m)) {
        if (str_ends_with(trim($val),'Z')) {
            try {
                $dt=new DateTime($val,new DateTimeZone('UTC'));
                $dt->setTimezone(new DateTimeZone(TZ));
                return ['date'=>$dt->format('Y-m-d'),'time'=>$dt->format('H:i'),'allday'=>false];
            } catch(Exception $e) {}
        }
        return ['date'=>"$m[1]-$m[2]-$m[3]",'time'=>sprintf('%02d:%02d',(int)$m[4],(int)$m[5]),'allday'=>false];
    }
    return ['date'=>null,'time'=>null,'allday'=>true];
}

function build_cal(): array {
    $c = cache_get('ical', TTL_ICAL);
    if ($c) return $c;

    $tz=new DateTimeZone(TZ); $today=new DateTime('today',$tz);
    $days=[]; $win_end='';
    for ($i=0;$i<CAL_DAYS;$i++) {
        $d=clone $today; $d->modify("+$i days"); $date=$d->format('Y-m-d');
        $days[$date]=['date'=>$date,'dow'=>$d->format('D'),'dom'=>$d->format('j'),
                      'is_today'=>($i===0),'is_weekend'=>in_array($d->format('N'),['6','7']),
                      'holiday'=>BC_HOLIDAYS[$date]??'','events_timed'=>[],'events_allday'=>[]];
        $win_end=$date;
    }
    $win_start=array_key_first($days);

    $all_events=[]; $ical_todos=[];
    foreach (ICAL_FEEDS as $url) {
        $raw=http_get($url);
        if (!$raw||!str_contains($raw,'BEGIN:VCALENDAR')) continue;
        $is_todo_cal = (bool)preg_match('/^X-WR-CALNAME:\s*Kendrick Stuff\s*$/mi', $raw);
        $text=ical_unfold($raw); $blocks=preg_split('/BEGIN:VEVENT/',$text);
        for ($i=1;$i<count($blocks);$i++) {
            $block=$blocks[$i]; $lines=preg_split('/\r?\n/',$block);
            $summary=''; $dtstart=$dtend=null;
            foreach ($lines as $line) {
                $u=strtoupper($line);
                if (str_starts_with($u,'SUMMARY:'))
                    $summary=strtr(trim(substr($line,strpos($line,':')+1)),['\\n'=>' ','\\,'=>',','\\;'=>';','\\\\'=>'\\']);
                if (str_starts_with($u,'DTSTART'))
                    $dtstart=parse_dt(trim(substr($line,strpos($line,':')+1)));
                if (str_starts_with($u,'DTEND')||str_starts_with($u,'DUE')) {
                    $dtend=parse_dt(trim(substr($line,strpos($line,':')+1)));
                    if ($dtend&&$dtend['allday']&&$dtend['date']) {
                        $dd=new DateTime($dtend['date'].'T12:00:00'); $dd->modify('-1 day'); $dtend['date']=$dd->format('Y-m-d');
                    }
                }
            }
            if ($summary&&$dtstart&&$dtstart['date']) {
                if ($is_todo_cal) {
                    $ical_todos[]=['text'=>$summary,'due'=>$dtstart['date'],'priority'=>3,'tags'=>[]];
                    continue;
                }
                if (preg_match('/^TODO:\s*(.+)$/i',$summary,$tm)) {
                    $ical_todos[]=['text'=>trim($tm[1]),'due'=>$dtstart['date'],'priority'=>3,'tags'=>[]];
                    continue;
                }
                $end_date=($dtend&&$dtend['date'])?$dtend['date']:$dtstart['date'];
                $all_events[]=['summary'=>$summary,'date'=>$dtstart['date'],'end_date'=>$end_date,
                               'time'=>$dtstart['time'],'allday'=>$dtstart['allday'],
                               'multi_day'=>$end_date>$dtstart['date']];
            }
        }
    }

    $seen=[];
    foreach ($all_events as $ev) {
        $k=$ev['summary'].'|'.$ev['date']; if (isset($seen[$k])) continue; $seen[$k]=true;
        if ($ev['multi_day']) {
            $start=max($ev['date'],$win_start); $end=min($ev['end_date'],$win_end);
            if ($start>$win_end||$end<$win_start) continue;
            $cur=new DateTime($start.'T12:00:00',$tz); $endDt=new DateTime($end.'T12:00:00',$tz);
            while ($cur<=$endDt) {
                $dd=$cur->format('Y-m-d');
                if (isset($days[$dd])) $days[$dd]['events_allday'][]=['summary'=>$ev['summary']];
                $cur->modify('+1 day');
            }
        } else {
            if (!isset($days[$ev['date']])) continue;
            $event=['summary'=>$ev['summary']];
            if ($ev['time']) { $event['time']=$ev['time']; $days[$ev['date']]['events_timed'][]=$event; }
            else             { $days[$ev['date']]['events_allday'][]=$event; }
        }
    }

    foreach ($days as &$day) {
        usort($day['events_timed'],fn($a,$b)=>strcmp($a['time']??'',$b['time']??''));
    }
    unset($day);

    $result=['days'=>array_values($days),'todos'=>$ical_todos];
    cache_set('ical',$result);
    return $result;
}

// ── Assemble ──────────────────────────────────────────────────────────────────
$wx   = build_wx();
$fc   = build_fc();
$cal  = build_cal();
$todo = build_todo($cal['todos'] ?? []);
$go   = ($game_param !== null) ? build_go($game_param) : null;

$sun_rise = $fc['days'][0]['sunrise'] ?? '';
$sun_set  = $fc['days'][0]['sunset']  ?? '';

// ── Flatten for Liquid templates ───────────────────────────────────────────
$flat = [];
$fc_today = $fc['days'][0] ?? [];

$flat['temp']        = $wx['temp']        ?? '';
$flat['feels']       = $wx['feels']       ?? '';
$flat['hi']          = $fc_today['hi']    ?? '';
$flat['lo']          = $fc_today['lo']    ?? '';
$flat['condition']   = $fc['current_condition'] ?? ($fc_today['condition'] ?? '');
$flat['wdir']        = $wx['wdir']        ?? '';
$flat['wind_kmh']    = $wx['wind_kmh']    ?? '';
$flat['gust_kmh']    = $wx['gust_kmh']    ?? '';
$flat['rain_day']    = $wx['rain_day']    ?? '';
$flat['rain_rate']   = $wx['rain_rate']   ?? '';
$flat['rain_week']   = $wx['rain_week']   ?? '';
$flat['humidity']    = $wx['humidity']    ?? '';
$flat['dew']         = $wx['dew']         ?? '';
$flat['pressure']    = $wx['pressure']    ?? '';
$flat['uvi']         = $wx['uvi']         ?? '';
$flat['solar']       = $wx['solar']       ?? '';
$flat['indoor_temp'] = $wx['indoor_temp'] ?? '';
$flat['indoor_hum']  = $wx['indoor_hum']  ?? '';

$flat['sunrise']    = $fc_today['sunrise'] ?? $sun_rise;
$flat['sunset']     = $fc_today['sunset']  ?? $sun_set;
$flat['moon_phase'] = $fc['moon_phase'] ?? 0;
$flat['moon_name']  = $fc['moon_name']  ?? '';

$cal_days = $cal['days'] ?? [];

// 7-day forecast strip (f0_*..f6_*) + that day's calendar events
for ($i=0;$i<=6;$i++) {
    $fday = $fc['days'][$i] ?? [];
    $flat["f{$i}_dow"]       = $fday['dow'] ?? '';
    $flat["f{$i}_hi"]        = $fday['hi']  ?? '';
    $flat["f{$i}_lo"]        = $fday['lo']  ?? '';
    $flat["f{$i}_condition"] = $fday['condition'] ?? '';
    $flat["f{$i}_pop_str"]   = (isset($fday['pop']) && $fday['pop']>0) ? $fday['pop'].'%' : '';
    $flat["f{$i}_mm_str"]    = (isset($fday['mm'])  && $fday['mm']>0)  ? $fday['mm'].'mm' : '';

    $cday   = $cal_days[$i] ?? [];
    $timed  = $cday['events_timed']  ?? [];
    $allday = $cday['events_allday'] ?? [];
    $flat["f{$i}_hol"]   = $cday['holiday'] ?? '';
    $flat["f{$i}_tev0n"] = $timed[0]['summary'] ?? ''; $flat["f{$i}_tev0t"] = $timed[0]['time'] ?? '';
    $flat["f{$i}_tev1n"] = $timed[1]['summary'] ?? ''; $flat["f{$i}_tev1t"] = $timed[1]['time'] ?? '';
    $flat["f{$i}_tmore"] = count($timed)  > 2 ? '+'.(count($timed)-2).' more'  : '';
    $flat["f{$i}_aev0"]  = $allday[0]['summary'] ?? '';
    $flat["f{$i}_aev1"]  = $allday[1]['summary'] ?? '';
    $flat["f{$i}_aev2"]  = $allday[2]['summary'] ?? '';
    $flat["f{$i}_amore"] = count($allday) > 3 ? '+'.(count($allday)-3).' more' : '';
}

// 2-week calendar grid (cal_d0_*..cal_d13_*)
foreach ($cal_days as $i => $d) {
    $dt = new DateTime($d['date']);
    $flat["cal_d{$i}_date"]        = $d['date'];
    $flat["cal_d{$i}_dow"]         = $d['dow'];
    $flat["cal_d{$i}_dom"]         = $d['dom'];
    $flat["cal_d{$i}_month_label"] = ($d['dom']=='1') ? $dt->format('M') : '';
    $flat["cal_d{$i}_today"]       = $d['is_today']   ? 'today' : '';
    $flat["cal_d{$i}_wknd"]        = $d['is_weekend'] ? 'wknd'  : '';
    $flat["cal_d{$i}_hol"]         = $d['holiday'] ?? '';

    $timed  = $d['events_timed']  ?? [];
    $allday = $d['events_allday'] ?? [];
    foreach ([0,1,2] as $j) {
        $flat["cal_d{$i}_tev{$j}n"] = $timed[$j]['summary'] ?? '';
        $flat["cal_d{$i}_tev{$j}t"] = $timed[$j]['time']    ?? '';
        $flat["cal_d{$i}_tev{$j}c"] = '';
        $flat["cal_d{$i}_aev{$j}"]  = $allday[$j]['summary'] ?? '';
    }
}

// Immediate to-do items (todo_u0_text..todo_u5_text)
for ($i=0;$i<=5;$i++) {
    $flat["todo_u{$i}_text"] = $todo['urgent'][$i]['text'] ?? '';
}

echo json_encode(array_merge([
    'go'   => $go,
    'wx'   => $wx,
    'fc'   => $fc,
    'sun'  => ['rise' => $sun_rise, 'set' => $sun_set],
    'moon' => ['phase' => $fc['moon_phase'] ?? 0, 'name' => $fc['moon_name'] ?? ''],
    'todo' => $todo,
    'cal'  => $cal,
], $flat), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
