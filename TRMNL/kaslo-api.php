<?php
/**
 * kaslo-api.php v4 - Unified TRMNL endpoint for go.* plugins
 *
 * Reads the local cache files written by the four existing APIs
 * (no HTTP self-referencing - reads files directly from disk).
 *
 * Cache files used:
 *   data/weather-cache.json  <- weather-device-api.php
 *   data/dgs-cache.json      <- dgs-device-api.php
 *   data/cal-cache-v3.json   <- cal-device-api.php
 *   todo.json                <- raw todos (processed here same as todo-device-api)
 *
 * External HTTP only for:
 *   Open-Meteo  -> moon phase + pressure sparkline
 *   DGS SGF     -> board position for selected game
 *
 * GET /kaslo-api.php?key=SECRET[&game=N]
 */

//  Config 
const DEVICE_KEY  = 'kw_40e818911c1980bcd56dc4aff37a820f811990a130eb771fd9e21a536edc55ea';
const BASE        = 'https://knotwork.ca';
const DATA_DIR    = __DIR__ . '/data';
const TODO_FILE   = __DIR__ . '/todo.json';
const CACHE_DIR   = '/tmp';

const DGS_SGF_BASE = 'https://www.dragongoserver.net/sgf.php?gid=';

const OPEN_METEO_URL =
    'https://api.open-meteo.com/v1/forecast?latitude=49.912&longitude=-116.908'
    . '&hourly=surface_pressure'
    . '&timezone=America%2FVancouver&forecast_days=1&past_days=3';

const TTL_OM  = 3600;   // 1 hour - moon phase + pressure
const TTL_SGF = 900;    // 15 min - board position

const REMINDER_ANCHOR = '2026-05-28';   // update when you refresh EcoWitt key
const REMINDER_TEXT   = 'Refresh EcoWitt Sharing API';

set_time_limit(60);
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate');

//  Auth 
$supplied = trim($_GET['key'] ?? '');
if ($supplied === '' || !hash_equals(DEVICE_KEY, $supplied)) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}
$game = isset($_GET['game']) ? max(0, (int)$_GET['game']) : null;
$g    = $game ?? 0;

//  Helpers 
function read_cache(string $path): ?array {
    if (!file_exists($path)) return null;
    $d = json_decode(file_get_contents($path), true);
    return is_array($d) ? $d : null;
}
function tmp_cache_get(string $name, int $ttl): ?array {
    $p = CACHE_DIR . '/kaslo4_' . $name . '.json';
    if (!file_exists($p) || time() - filemtime($p) >= $ttl) return null;
    $d = json_decode(file_get_contents($p), true);
    return is_array($d) ? $d : null;
}
function tmp_cache_set(string $name, array $d): void {
    file_put_contents(CACHE_DIR . '/kaslo4_' . $name . '.json', json_encode($d), LOCK_EX);
}
function http_get(string $url): ?string {
    $ch = curl_init($url);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER=>true, CURLOPT_TIMEOUT=>15,
                            CURLOPT_CONNECTTIMEOUT=>5, CURLOPT_USERAGENT=>'kaslo-api/4.0',
                            CURLOPT_FOLLOWLOCATION=>true]);
    $r = curl_exec($ch); curl_close($ch);
    return ($r && trim($r) !== '') ? $r : null;
}

//  1. Weather (from weather-device-api cache) 
$wx_flat = read_cache(DATA_DIR . '/weather-cache.json') ?? [];

// Build fc.days[] from f0_*  f6_* flat vars
$fc_days = [];
for ($i = 0; $i <= 6; $i++) {
    $p = "f{$i}_";
    if (!isset($wx_flat["{$p}dow"])) break;
    $fc_days[] = [
        'dow'       => $wx_flat["{$p}dow"]       ?? '',
        'hi'        => $wx_flat["{$p}hi"]        ?? '',
        'lo'        => $wx_flat["{$p}lo"]        ?? '',
        'condition' => $wx_flat["{$p}condition"] ?? '',
        'pop'       => $wx_flat["{$p}pop_str"]   ?? '',
        'mm'        => $wx_flat["{$p}mm_str"]    ?? '',
    ];
}

$wx = $wx_flat;
$fc = [
    'days'              => $fc_days,
    'current_condition' => $wx_flat['condition'] ?? '',
    'pressure_now'      => $wx_flat['pressure']  ?? '',
];
$sun = ['rise' => $wx_flat['sunrise'] ?? '', 'set' => $wx_flat['sunset'] ?? ''];

//  2. Moon phase + pressure history (Open-Meteo, cached 1hr) 
$om_cache = tmp_cache_get('om', TTL_OM);
if (!$om_cache) {
    $om_raw = http_get(OPEN_METEO_URL);
    if ($om_raw) {
        $om = json_decode($om_raw, true);
        if (is_array($om)) {
            $phase = compute_moon_phase();
            // Same name lookup as plugin-weather-v4.md JS (age-based, not phase*8)
            $age_days = $phase * 29.53058867;
            $names    = ['New Moon','Waxing Crescent','First Quarter','Waxing Gibbous',
                         'Full Moon','Waning Gibbous','Last Quarter','Waning Crescent'];
            $bounds   = [1.85, 7.38, 9.22, 14.77, 16.61, 22.15, 23.99, 29.53];
            $name_idx = count($bounds) - 1;
            foreach ($bounds as $bi => $bv) {
                if ($age_days < $bv) { $name_idx = $bi; break; }
            }
            $ph    = array_values($om['hourly']['surface_pressure'] ?? []);
            $pn    = count($ph);
            $cp    = $pn > 0 ? (float)$ph[$pn-1] : 0;
            $pp    = $pn > 3 ? (float)$ph[$pn-4] : $cp;
            $delta = $cp - $pp;
            $om_cache = [
                'phase'    => $phase,
                'name'     => $names[$name_idx],
                'p_json'   => json_encode($ph, JSON_UNESCAPED_SLASHES),
                'p_now'    => round($cp, 1),
                'p_trend'  => $delta > 0.5 ? 'rising' : ($delta < -0.5 ? 'falling' : 'steady'),
            ];
            tmp_cache_set('om', $om_cache);
        }
    }
}

$fc['pressure_trend']        = $om_cache['p_trend'] ?? 'steady';
$fc['pressure_now']          = (!empty($om_cache['p_now'])) ? $om_cache['p_now'] : ($wx_flat['pressure'] ?? '');
$fc['pressure_history_json'] = $om_cache['p_json']  ?? '[]';
$moon = ['phase' => $om_cache['phase'] ?? 0, 'name' => $om_cache['name'] ?? ''];

//  3. DGS game + board (from dgs-device-api cache + SGF fetch) 
$go = null;
if ($game !== null) {
    $dgs = read_cache(DATA_DIR . '/dgs-cache.json') ?? [];
    $gid = $dgs["g{$g}_id"] ?? ($dgs['g0_id'] ?? null);

    $board = ['last_col'=>-1,'last_row'=>-1,'last_color'=>'',
              'board_black_json'=>'[]','board_white_json'=>'[]','sgf'=>''];
    if ($gid) {
        $bc = tmp_cache_get('brd_'.$gid, TTL_SGF);
        if (!$bc) {
            $sgf_raw = http_get(DGS_SGF_BASE . $gid);
            if ($sgf_raw) {
                $bc = replay_sgf($sgf_raw);
                $bc['sgf'] = $sgf_raw;   // store raw SGF for JS fallback
                tmp_cache_set('brd_'.$gid, $bc);
            }
        }
        if ($bc) $board = $bc;
    }

    $go = array_merge($board, [
        'game_id'       => $gid ?? 0,
        'opponent'      => $dgs["g{$g}_opp"]   ?? '',
        'color'         => $dgs["g{$g}_col"]   ?? '',
        'moves'         => $dgs["g{$g}_moves"] ?? 0,
        'time_left'     => $dgs["g{$g}_time"]  ?? '',
        'my_turn'       => (bool)($dgs["g{$g}_yours"] ?? 0),
        'my_turn_count' => (int)($dgs['my_turn_count'] ?? 0),
        'total_games'   => (int)($dgs['total'] ?? 0),
        'game_index'    => $g,
    ]);
}

//  4. Todo (from todo.json, same logic as todo-device-api.php) 
$todo = ['urgent'=>[],'rest'=>[],'total'=>0];
if (file_exists(TODO_FILE)) {
    $all = json_decode(file_get_contents(TODO_FILE), true) ?? [];
    $tz  = new DateTimeZone('America/Vancouver');
    $today    = (new DateTime('today',$tz))->format('Y-m-d');
    $tomorrow = (new DateTime('tomorrow',$tz))->format('Y-m-d');
    $in3      = (new DateTime('+3 days',$tz))->format('Y-m-d');
    $fmt = function(?string $d) use ($today,$tomorrow,$tz): string {
        if (!$d) return '';
        if ($d===$today) return 'Today';
        if ($d===$tomorrow) return 'Tomorrow';
        $days=(int)(new DateTime('today',$tz))->diff(new DateTime($d,$tz))->format('%r%a');
        return $days<0 ? abs($days).'d overdue' : (new DateTime($d,$tz))->format('M j');
    };
    $urgent=[]; $rest=[];
    foreach ($all as $item) {
        if (!empty($item['done'])) continue;
        if (($item['recurWeekday']??'')!==''||($item['recurDay']??'')!=='') continue;
        $pri=(int)($item['priority']??5); $due=$item['due']??'';
        $tags=(array)($item['tags']??[]);
        $out=['text'=>$item['text']??'','priority'=>$pri,
              'due_label'=>$fmt($due?:null),
              'tags'=>array_values($tags),'tags_str'=>implode(' ',$tags)];
        if ($pri<=2||(!empty($due)&&$due<=$in3)) { $urgent[]=$out; } else { $rest[]=$out; }
    }
    usort($urgent,fn($a,$b)=>strcmp($a['due_label']?:'ZZZ',$b['due_label']?:'ZZZ')?:$a['priority']-$b['priority']);
    usort($rest,  fn($a,$b)=>$a['priority']-$b['priority']?:strcmp($a['text'],$b['text']));

    // EcoWitt reminder
    $rem_due  = reminder_due();
    $rem_days = (int)(new DateTime('today',$tz))->diff(new DateTime($rem_due,$tz))->format('%r%a');
    $rem = ['text'=>REMINDER_TEXT,'priority'=>2,'due_label'=>$fmt($rem_due),'tags'=>['ecowitt'],'tags_str'=>'ecowitt'];
    if ($rem_days<=3) { array_unshift($urgent,$rem); } else { $rest[]=$rem; }

    $todo = ['urgent'=>$urgent,'rest'=>$rest,'total'=>count($urgent)+count($rest)];
}

// Moon phase — Julian Date calculation matching plugin-weather-v4.md JS exactly
function compute_moon_phase(): float {
    $SYN    = 29.53058867;
    $JD_REF = 2451549.76; // Jan 6 2000 new moon
    $tz     = new DateTimeZone('America/Vancouver');
    $now    = new DateTime('now', $tz);
    $y = (int)$now->format('Y');
    $m = (int)$now->format('n');
    $d = (int)$now->format('j');
    $h = (int)$now->format('H') / 24 + (int)$now->format('i') / 1440;
    if ($m <= 2) { $y--; $m += 12; }
    $A  = (int)($y / 100);
    $JD = floor(365.25 * ($y + 4716)) + floor(30.6001 * ($m + 1)) + $d + $h + 2 - $A + floor($A / 4) - 1524.5;
    $age = fmod($JD - $JD_REF, $SYN);
    if ($age < 0) $age += $SYN;
    return round($age / $SYN, 4); // 0=new, 0.5=full
}

function cond_icon(string $s): string {
    $c = strtolower(trim($s));
    if (!$c) return '';
    if (strpos($c,'clear')!==false)   return '&#x2600;';
    if (strpos($c,'mostly clear')!==false||strpos($c,'partly')!==false) return '&#x26C5;';
    if (strpos($c,'drizzle')!==false) return '&#x2602;';
    if (strpos($c,'snow')!==false)    return '&#x2744;';
    if (strpos($c,'thunder')!==false||strpos($c,'storm')!==false) return '&#x26A1;';
    if (strpos($c,'rain')!==false||strpos($c,'shower')!==false) return '&#x2614;';
    if (strpos($c,'fog')!==false||strpos($c,'cloud')!==false||strpos($c,'overcast')!==false) return '&#x2601;';
    return '&#x2601;';
}

//  5. Calendar (from cal-device-api cache)
$cal_flat = read_cache(DATA_DIR . '/cal-cache-v3.json') ?? [];
$cal_days = [];
$prev_month_abbr = '';
for ($i = 0; $i <= 20; $i++) {   // 21 days for Dashboard plugin
    $p = "d{$i}_";
    if (!isset($cal_flat["{$p}dow"])) break;
    $timed=[];
    for ($t=0;$t<=1;$t++) {
        $n=$cal_flat["{$p}tev{$t}n"]??'';
        if ($n!=='') $timed[]=['summary'=>$n,'time'=>$cal_flat["{$p}tev{$t}t"]??''];
    }
    $allday=[];
    for ($a=0;$a<=2;$a++) {
        $s=$cal_flat["{$p}aev{$a}"]??'';
        if ($s!=='') $allday[]=['summary'=>$s];
    }
    $date_str = $cal_flat["{$p}date"] ?? '';
    $cur_month_abbr = $date_str ? (new DateTime($date_str, $tz))->format('M') : '';
    $month_label = ($cur_month_abbr !== $prev_month_abbr) ? $cur_month_abbr : '';
    $prev_month_abbr = $cur_month_abbr;
    $day=['dow'=>$cal_flat["{$p}dow"]??'','dom'=>$cal_flat["{$p}dom"]??'',
          'date'=>$date_str,
          'month_label'=>$month_label,
          'is_today'=>($cal_flat["{$p}today"]??'')==='today',
          'is_weekend'=>($cal_flat["{$p}wknd"]??'')==='wknd',
          'holiday'=>$cal_flat["{$p}hol"]??'',
          'events_timed'=>$timed,'events_allday'=>$allday];
    // Inject EcoWitt reminder if this day matches
    if (($day['date']??'')===$rem_due??'') array_unshift($day['events_allday'],['summary'=>REMINDER_TEXT]);
    $cal_days[]=$day;
}

//  EcoWitt reminder helper 
function reminder_due(): string {
    $tz=new DateTimeZone('America/Vancouver');
    $anc=new DateTime(REMINDER_ANCHOR,$tz); $tod=new DateTime('today',$tz);
    if ($anc>=$tod) return $anc->format('Y-m-d');
    $elapsed=(int)$anc->diff($tod)->days; $periods=(int)floor($elapsed/30);
    $c=clone $anc; $c->modify('+'.($periods*30).' days');
    if ($c<$tod) $c->modify('+30 days');
    return $c->format('Y-m-d');
}

//  SGF board replay 
function replay_sgf(string $sgf): array {
    $b=[]; for($r=0;$r<19;$r++) $b[$r]=array_fill(0,19,'');
    $lc=-1;$lr=-1;$lcol='';
    preg_match_all('/A([BW])\[([a-s]{2})\]/i',$sgf,$s,PREG_SET_ORDER);
    foreach($s as $x){$c=ord($x[2][0])-97;$r=ord($x[2][1])-97;if($c>=0&&$c<19&&$r>=0&&$r<19)$b[$r][$c]=strtoupper($x[1]);}
    preg_match_all('/;([BW])\[([a-s]{0,2})\]/',$sgf,$m,PREG_SET_ORDER);
    foreach($m as $mv){
        $col=$mv[1];$coord=$mv[2];if(strlen($coord)<2)continue;
        $c=ord($coord[0])-97;$r=ord($coord[1])-97;
        if($c<0||$c>=19||$r<0||$r>=19||$b[$r][$c]!=='')continue;
        $b[$r][$c]=$col;$lc=$c;$lr=$r;$lcol=$col;
        $opp=$col==='B'?'W':'B';
        foreach([[-1,0],[1,0],[0,-1],[0,1]]as[$dr,$dc]){$nr=$r+$dr;$nc=$c+$dc;if($nr>=0&&$nr<19&&$nc>=0&&$nc<19&&$b[$nr][$nc]===$opp)sgf_remove($b,$nr,$nc,$opp);}
        sgf_remove($b,$r,$c,$col);
    }
    $bk=[];$wh=[];
    for($r=0;$r<19;$r++)for($c=0;$c<19;$c++){if($b[$r][$c]==='B')$bk[]=[$c,$r];elseif($b[$r][$c]==='W')$wh[]=[$c,$r];}
    return['last_col'=>$lc,'last_row'=>$lr,'last_color'=>$lcol,
           'board_black_json'=>json_encode($bk,JSON_UNESCAPED_SLASHES),
           'board_white_json'=>json_encode($wh,JSON_UNESCAPED_SLASHES)];
}
function sgf_group(array &$b,int $r,int $c,string $col):array{
    $vis=[];$q=[[$r,$c]];$libs=0;$st=[];
    while(!empty($q)){[$cr,$cc]=array_pop($q);$k=$cr*19+$cc;if(isset($vis[$k]))continue;$vis[$k]=true;$st[]=[$cr,$cc];
    foreach([[-1,0],[1,0],[0,-1],[0,1]]as[$dr,$dc]){$nr=$cr+$dr;$nc=$cc+$dc;if($nr<0||$nr>=19||$nc<0||$nc>=19)continue;$nk=$nr*19+$nc;
    if($b[$nr][$nc]==='')$libs++;elseif($b[$nr][$nc]===$col&&!isset($vis[$nk]))$q[]=[$nr,$nc];}}
    return['stones'=>$st,'liberties'=>$libs];
}
function sgf_remove(array &$b,int $r,int $c,string $col):void{
    $g=sgf_group($b,$r,$c,$col);if($g['liberties']===0)foreach($g['stones']as[$sr,$sc])$b[$sr][$sc]='';
}

//  Output - flat top-level vars (single polling URL pattern)
// Scalar vars use {{ var_name }}, arrays use {% for item in var_name %}
$rem_due = reminder_due();

$out = [];

// Go board + game meta  (go_*)
if ($go) {
    $out['go_opponent']         = $go['opponent']      ?? '';
    $out['go_color']            = strtolower($go['color'] ?? '');
    $out['go_color_upper']      = strtoupper($go['color'] ?? '');
    $out['go_moves']            = $go['moves']         ?? 0;
    $out['go_time_left']        = $go['time_left']     ?? '';
    $out['go_my_turn']          = $go['my_turn']       ? 'true' : '';
    $out['go_my_turn_count']    = $go['my_turn_count'] ?? 0;
    $out['go_total_games']      = $go['total_games']   ?? 0;
    $out['go_sgf_b64']          = base64_encode($go['sgf'] ?? '');
    $out['go_board_black_json'] = $go['board_black_json'] ?? '[]';
    $out['go_board_white_json'] = $go['board_white_json'] ?? '[]';
    $out['go_last_col']         = $go['last_col']      ?? -1;
    $out['go_last_row']         = $go['last_row']      ?? -1;
    $out['go_last_color']       = $go['last_color']    ?? '';
}

// Weather current (wx_*) - pass through flat vars from weather cache
$wx_keys = ['temp','temp_full','feels','condition','hi','lo','humidity','dew',
            'wind_kmh','gust_kmh','wdir','pressure','uvi','solar',
            'rain_day','rain_rate','rain_week','indoor_temp','indoor_hum',
            'sunrise','sunset','daylight'];
foreach ($wx_keys as $k) {
    $out['wx_'.$k] = $wx_flat[$k] ?? '';
    $out[$k]       = $wx_flat[$k] ?? '';  // bare alias for plugin-weather-v4 backward compat
}

// 7-day forecast - pass through f0_* to f6_* directly from weather cache
for ($i = 0; $i <= 6; $i++) {
    foreach (['dow','hi','lo','condition','pop_str','mm_str',
              'hol','tev0n','tev0t','tev1n','tev1t','tmore',
              'aev0','aev1','aev2','amore'] as $field) {
        $key = "f{$i}_{$field}";
        $out[$key] = $wx_flat[$key] ?? '';
    }
}

// Pressure + moon (fc_* / moon_*)
$out['fc_condition']          = $wx_flat['condition']    ?? '';
$out['fc_pressure_now']       = $fc['pressure_now']      ?? '';
$out['fc_pressure_trend']     = $fc['pressure_trend']    ?? 'steady';
$out['fc_pressure_json']      = $fc['pressure_history_json'] ?? '[]';

$out['sun_rise']   = $wx_flat['sunrise'] ?? '';
$out['sun_set']    = $wx_flat['sunset']  ?? '';
$out['moon_phase'] = $moon['phase'] ?? 0;
$out['moon_name']  = $moon['name']  ?? '';

// Todo — flat indexed vars (no for-loop needed in template)
$out['todo_total'] = $todo['total'] ?? 0;
$urgent = $todo['urgent'] ?? [];
$rest   = $todo['rest']   ?? [];
for ($i = 0; $i < 6; $i++) {
    $it = $urgent[$i] ?? null;
    $out["todo_u{$i}_text"] = $it ? $it['text'] : '';
    $out["todo_u{$i}_meta"] = $it ? trim(($it['due_label'] ?? '').' '.($it['tags_str'] ?? '')) : '';
}
$out['todo_u_more'] = count($urgent) > 6 ? '+' . (count($urgent) - 6) . ' more' : '';
for ($i = 0; $i < 8; $i++) {
    $it = $rest[$i] ?? null;
    $out["todo_r{$i}_text"] = $it ? $it['text'] : '';
    $out["todo_r{$i}_meta"] = $it ? ($it['due_label'] ?? '') : '';
}
$out['todo_r_more'] = count($rest) > 8 ? '+' . (count($rest) - 8) . ' more' : '';

// Calendar — flat indexed vars (matches cal-device-api proven format)
foreach ($cal_days as $i => $day) {
    $p = "cal_d{$i}_";
    $out["{$p}dow"]         = $day['dow']          ?? '';
    $out["{$p}dom"]         = $day['dom']          ?? '';
    $out["{$p}date"]        = $day['date']         ?? '';
    $out["{$p}month_label"] = $day['month_label']  ?? '';
    $out["{$p}today"] = ($day['is_today']   ?? false) ? 'today' : '';
    $out["{$p}wknd"]  = ($day['is_weekend'] ?? false) ? 'wknd'  : '';
    $out["{$p}hol"]   = $day['holiday'] ?? '';
    for ($t = 0; $t < 2; $t++) {
        $ev = $day['events_timed'][$t] ?? null;
        $out["{$p}tev{$t}n"] = $ev ? $ev['summary'] : '';
        $out["{$p}tev{$t}t"] = $ev ? $ev['time']    : '';
    }
    $out["{$p}tmore"] = count($day['events_timed'] ?? []) > 2
        ? '+' . (count($day['events_timed']) - 2) . ' more' : '';
    for ($a = 0; $a < 3; $a++) {
        $out["{$p}aev{$a}"] = $day['events_allday'][$a]['summary'] ?? '';
    }
    $out["{$p}f_cond"] = $wx_flat["f{$i}_condition"] ?? '';
    $out["{$p}f_icon"] = cond_icon($wx_flat["f{$i}_condition"] ?? '');
    $out["{$p}f_hi"]   = $wx_flat["f{$i}_hi"]        ?? '';
    $out["{$p}f_lo"]   = $wx_flat["f{$i}_lo"]        ?? '';
    $out["{$p}f_pop"]  = $wx_flat["f{$i}_pop_str"]   ?? '';
    $out["{$p}f_mm"]   = $wx_flat["f{$i}_mm_str"]    ?? '';
}

echo json_encode($out, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
