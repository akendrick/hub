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
const DEVICE_KEY    = 'REPLACE_WITH_YOUR_SECRET_KEY';
const DGS_USER      = 'shrimphead';
const TODOS_FILE    = __DIR__ . '/data/todos.json';
const CACHE_DIR     = '/tmp';
const TZ            = 'America/Vancouver';
const FETCH_TIMEOUT = 10;
const CAL_DAYS      = 7;

// Paste your full EcoWitt real-time URL here (was IDX_0 in KasloTRMNL)
const ECOWITT_URL = 'REPLACE_WITH_YOUR_ECOWITT_REAL_TIME_URL';

// Open-Meteo — 3 days past hourly pressure + 7-day forecast + moon phase
const OPEN_METEO_URL =
    'https://api.open-meteo.com/v1/forecast?latitude=49.912&longitude=-116.908'
    . '&current=weather_code'
    . '&daily=temperature_2m_max,temperature_2m_min,precipitation_sum,'
    .   'precipitation_probability_max,weather_code,sunrise,sunset,moon_phase'
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

    if (ECOWITT_URL === 'REPLACE_WITH_YOUR_ECOWITT_REAL_TIME_URL') {
        return ['error' => 'not_configured'];
    }
    $raw = http_get(ECOWITT_URL);
    if (!$raw) return ['error' => 'fetch_failed'];

    $d = json_decode($raw, true);
    $o = $d['data'] ?? [];

    $wd = (float)ecowitt_val($o, 'wind', 'wind_direction', 'value');

    $result = [
        'temp'        => (string)(int)floatval(ecowitt_val($o, 'outdoor',      'temperature',   'value')),
        'temp_full'   => ecowitt_val($o, 'outdoor', 'temperature', 'value'),
        'feels'       => (string)(int)floatval(ecowitt_val($o, 'outdoor',      'feels_like',    'value')),
        'rain_day'    => ecowitt_val($o, 'rainfall',     'daily',         'value'),
        'rain_rate'   => ecowitt_val($o, 'rainfall',     'rain_rate',     'value'),
        'rain_week'   => ecowitt_val($o, 'rainfall',     'weekly',        'value'),
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

    // Moon phase
    $moon_phase = (float)($d['daily']['moon_phase'][0] ?? 0);

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
        ($mine ? $my : $their)[] = $g;
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
function build_todo(): array {
    if (!file_exists(TODOS_FILE)||!is_readable(TODOS_FILE))
        return ['urgent'=>[],'rest'=>[],'total'=>0];

    $all = json_decode(file_get_contents(TODOS_FILE),true);
    if (!is_array($all)) return ['urgent'=>[],'rest'=>[],'total'=>0];

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
        $is_urgent=$pri<=2||(!empty($due)&&$due<=$in3);
        $tags=(array)($item['tags']??[]);
        $out=['text'=>$item['text']??'','priority'=>$pri,'due_label'=>$fmt($due?:null),
              'tags'=>array_values($tags),'tags_str'=>implode(' ',$tags)];
        ($is_urgent?$urgent:$rest)[]=$out;
    }
    usort($urgent,fn($a,$b)=>strcmp($a['due_label']?:'ZZZ',$b['due_label']?:'ZZZ')?:$a['priority']-$b['priority']);
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

    $all_events=[];
    foreach (ICAL_FEEDS as $url) {
        $raw=http_get($url);
        if (!$raw||!str_contains($raw,'BEGIN:VCALENDAR')) continue;
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

    $result=['days'=>array_values($days)];
    cache_set('ical',$result);
    return $result;
}

// ── Assemble ──────────────────────────────────────────────────────────────────
$wx   = build_wx();
$fc   = build_fc();
$todo = build_todo();
$cal  = build_cal();
$go   = ($game_param !== null) ? build_go($game_param) : null;

$sun_rise = $fc['days'][0]['sunrise'] ?? '';
$sun_set  = $fc['days'][0]['sunset']  ?? '';

echo json_encode([
    'go'   => $go,
    'wx'   => $wx,
    'fc'   => $fc,
    'sun'  => ['rise' => $sun_rise, 'set' => $sun_set],
    'moon' => ['phase' => $fc['moon_phase'] ?? 0, 'name' => $fc['moon_name'] ?? ''],
    'todo' => $todo,
    'cal'  => $cal,
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
