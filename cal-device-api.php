<?php
/**
 * cal-device-api.php v2  14-day calendar JSON for TRMNL (with caching)
 *
 * Key change from v1: responses are served from a local cache file.
 * iCal fetching only happens when the cache is stale. This prevents
 * TRMNL read-timeout errors caused by slow sequential iCloud fetches.
 *
 * Usage:  GET /cal-device-api.php?key=YOUR_DEVICE_KEY
 */

const DEVICE_KEY  = 'kw_40e818911c1980bcd56dc4aff37a820f811990a130eb771fd9e21a536edc55ea';
const WINDOW_DAYS = 28;
const FETCH_TIMEOUT = 7;

// Cache file  must be writable by the web server.
// data/ dir should already exist from todo-device-api.php
const CACHE_FILE = __DIR__ . '/data/cal-cache-v3.json';

// Rebuild cache if older than 20 minutes.
const CACHE_TTL = 1200;

$CALS = [
    ['url' => 'https://p162-caldav.icloud.com/published/2/MTI5MzgzNTk0MTI5MzgzNWY9cTYgK0OwIRz4UCfQYKvJY44bQNHC73gDwS5V1U5jUkV9ynvy7uipCHeNIfenut1Eq0LNCaulcS6IDwnCXzpP_iBTsC0Fc21d3SMFtSvB_1CnVBymPyAyPnzFyGkhsg', 'label' => 'personal'],
    ['url' => 'https://p102-caldav.icloud.com/published/2/MTI5MzgzNTk0MTI5MzgzNWY9cTYgK0OwIRz4UCfQYKvnN6NvIE36DmOUbrBgiLcGN7ezhsYo-YXFxAw38AN_vpaiEFRiXefJROr9Az80VcU', 'label' => 'personal2'],
    ['url' => 'https://p101-caldav.icloud.com/published/2/Mjc4Mjk1ODMxMjc4Mjk1OPiLHZnPp67Ltgtp3v229x8qT-uPdlC-Sg6bv_JZdLUiimpxJVvfu-OL9CBtnZ3CMevVIgwICabIi9WTyZIKqHA', 'label' => 'personal3'],
];

const BC_HOLIDAYS = [
    '2026-01-01' => "New Year's Day",   '2026-02-16' => 'Family Day',
    '2026-04-03' => 'Good Friday',      '2026-05-18' => 'Victoria Day',
    '2026-07-01' => 'Canada Day',       '2026-08-03' => 'BC Day',
    '2026-09-07' => 'Labour Day',       '2026-09-30' => 'Truth & Rec.',
    '2026-10-12' => 'Thanksgiving',     '2026-11-11' => 'Remembrance Day',
    '2026-12-25' => 'Christmas Day',
];

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$supplied = trim($_GET['key'] ?? '');
if ($supplied === '' || !hash_equals(DEVICE_KEY, $supplied)) {
    http_response_code(401); echo json_encode(['error'=>'Unauthorized']); exit;
}

//  Serve from cache if fresh 
$cacheAge = file_exists(CACHE_FILE) ? (time() - filemtime(CACHE_FILE)) : PHP_INT_MAX;
if ($cacheAge < CACHE_TTL) {
    $cached = @file_get_contents(CACHE_FILE);
    if ($cached !== false && strlen($cached) > 10) { echo $cached; exit; }
}

//  Rebuild cache 
function fetch_url(string $url): ?string {
    $ctx = stream_context_create(['http' => [
        'timeout' => FETCH_TIMEOUT, 'ignore_errors' => true,
        'user_agent' => 'Mozilla/5.0 (compatible; knotwork-cal/2.0)',
    ]]);
    $b = @file_get_contents($url, false, $ctx);
    return ($b && strpos($b, 'BEGIN:VCALENDAR') !== false) ? $b : null;
}

function ical_unfold(string $t): string { return preg_replace('/\r?\n[ \t]/', '', $t); }

function abbreviate_names(string $s): string {
    return str_ireplace(
        ['Sarah',  'Skylet', 'Kendrick'],
        ['SA',     'SK',     'KL'],
        $s
    );
}

function parse_dt(string $v): array {
    $v = trim($v);
    if (preg_match('/^(\d{4})(\d{2})(\d{2})$/', $v, $m))
        return ['date'=>"$m[1]-$m[2]-$m[3]",'time'=>null,'allday'=>true];
    if (preg_match('/^(\d{4})(\d{2})(\d{2})T(\d{2})(\d{2})/', $v, $m)) {
        if (str_ends_with(trim($v),'Z')) {
            try {
                $dt = new DateTime($v, new DateTimeZone('UTC'));
                $dt->setTimezone(new DateTimeZone('America/Vancouver'));
                return ['date'=>$dt->format('Y-m-d'),'time'=>$dt->format('H:i'),'allday'=>false];
            } catch(Exception $e){}
        }
        return ['date'=>"$m[1]-$m[2]-$m[3]",'time'=>sprintf('%02d:%02d',$m[4],$m[5]),'allday'=>false];
    }
    return ['date'=>null,'time'=>null,'allday'=>true];
}

function rrule_until_to_ymd(string $raw): string {
    // Normalise UNTIL value (20260602 or 20260602T140000Z) to Y-m-d (2026-06-02)
    $d = preg_replace('/T.*$/', '', trim($raw)); // strip time component
    if (preg_match('/^(\d{4})(\d{2})(\d{2})$/', $d, $m))
        return "$m[1]-$m[2]-$m[3]";
    return $d; // already formatted or unrecognised
}

function expand_rrule(array $ev, string $w0, string $wN): array {
    // Expand FREQ=DAILY/WEEKLY/MONTHLY recurring events into the window [w0,wN].
    // Handles UNTIL (date or datetime), COUNT, and INTERVAL.
    $rrule = $ev['rrule'] ?? '';
    if (!$rrule) return [$ev];
    $parts = [];
    foreach (explode(';', $rrule) as $p) {
        [$k,$v] = explode('=', $p, 2) + [1=>''];
        $parts[strtoupper($k)] = $v;
    }
    $freq  = strtoupper($parts['FREQ'] ?? '');
    if (!in_array($freq, ['DAILY','WEEKLY','MONTHLY'])) return [$ev];

    // Normalise UNTIL to Y-m-d string for safe comparison
    $until = isset($parts['UNTIL']) ? rrule_until_to_ymd($parts['UNTIL']) : null;
    $count = isset($parts['COUNT']) ? (int)$parts['COUNT'] : PHP_INT_MAX;
    $interval = max(1, (int)($parts['INTERVAL'] ?? 1));

    // Step string for DateTime::modify()
    if ($freq === 'DAILY')        $step = "+{$interval} day";
    elseif ($freq === 'WEEKLY')   $step = "+{$interval} week";
    else /* MONTHLY */            $step = "+{$interval} month";

    $tz  = new DateTimeZone('America/Vancouver');
    $cur = new DateTime($ev['date'] . ($ev['time'] ? 'T'.str_replace(':','',$ev['time']).'00' : 'T120000'), $tz);
    $n   = 0;
    $results = [];
    while ($cur->format('Y-m-d') <= $wN && $n < $count) {
        $d = $cur->format('Y-m-d');
        if ($until && $d > $until) break;
        if ($d >= $w0) {
            $occ = $ev;
            $occ['date']      = $d;
            $occ['end_date']  = $d;
            $occ['multi_day'] = false;
            $results[] = $occ;
        }
        $n++;
        $cur->modify($step);
    }
    return $results ?: [$ev]; // fallback: original event
}

function parse_ical(string $raw, string $label): array {
    $blocks = preg_split('/BEGIN:VEVENT/', ical_unfold($raw));
    $out = [];
    for ($i=1;$i<count($blocks);$i++) {
        $summary=''; $dtstart=$dtend=null; $rrule=''; $exdates=[];
        foreach (preg_split('/\r?\n/', $blocks[$i]) as $line) {
            $u = strtoupper($line);
            if (str_starts_with($u,'SUMMARY:'))
                $summary = abbreviate_names(strtr(trim(substr($line,strpos($line,':')+1)),['\\n'=>' ','\\,'=>',','\\;'=>';','\\\\'=>'\\']));
            if (str_starts_with($u,'DTSTART'))
                $dtstart = parse_dt(substr($line,strpos($line,':')+1));
            if (str_starts_with($u,'DTEND')||str_starts_with($u,'DUE')) {
                $dtend = parse_dt(substr($line,strpos($line,':')+1));
                if ($dtend&&$dtend['allday']&&$dtend['date']) {
                    $d=new DateTime($dtend['date'].'T12:00:00'); $d->modify('-1 day');
                    $dtend['date']=$d->format('Y-m-d');
                }
            }
            if (str_starts_with($u,'RRULE:'))
                $rrule = trim(substr($line, 6));
            if (str_starts_with($u,'EXDATE')) {
                // collect exception dates
                $exval = substr($line, strpos($line,':')+1);
                foreach (explode(',', $exval) as $ex)
                    $exdates[] = substr(trim($ex), 0, 10);
            }
        }
        if ($summary&&$dtstart&&$dtstart['date']) {
            $end = ($dtend&&$dtend['date']) ? $dtend['date'] : $dtstart['date'];
            $ev = ['summary'=>$summary,'date'=>$dtstart['date'],'end_date'=>$end,
                   'time'=>$dtstart['time'],'allday'=>$dtstart['allday'],
                   'multi_day'=>($end>$dtstart['date']),'cal'=>$label,'rrule'=>$rrule,'exdates'=>$exdates];
            $out[] = $ev;
        }
    }
    return $out;
}

$tz    = new DateTimeZone('America/Vancouver');
$today = new DateTime('today', $tz);
$days  = [];
for ($i=0;$i<WINDOW_DAYS;$i++) {
    $d=clone $today; $d->modify("+$i days"); $date=$d->format('Y-m-d');
    $days[$date]=['date'=>$date,'dow'=>$d->format('D'),'dom'=>$d->format('j'),
        'is_today'=>($i===0),'is_weekend'=>in_array($d->format('N'),['6','7']),
        'holiday'=>BC_HOLIDAYS[$date]??'','events'=>[]];
}
$w0=array_key_first($days); $wN=array_key_last($days);

global $CALS; $all=[];
foreach ($CALS as $cal) { $r=fetch_url($cal['url']); if($r) foreach(parse_ical($r,$cal['label']) as $e) $all[]=$e; }

// Expand recurring events, then process all occurrences
$expanded = [];
foreach ($all as $ev) {
    if ($ev['rrule']) {
        foreach (expand_rrule($ev, $w0, $wN) as $occ) $expanded[] = $occ;
    } else {
        $expanded[] = $ev;
    }
}

$seen=[];
foreach ($expanded as $ev) {
    // Skip EXDATE exceptions
    if (in_array($ev['date'], $ev['exdates'] ?? [])) continue;
    $key=$ev['summary'].'|'.$ev['date'];
    if (isset($seen[$key])) continue; $seen[$key]=true;
    if ($ev['multi_day']) {
        $s=max($ev['date'],$w0); $e=min($ev['end_date'],$wN);
        if ($s>$wN||$e<$w0) continue;
        $cur=new DateTime($s.'T12:00:00',$tz); $end=new DateTime($e.'T12:00:00',$tz);
        while ($cur<=$end) { $dd=$cur->format('Y-m-d');
            if (isset($days[$dd])) $days[$dd]['events'][]=['summary'=>$ev['summary'],'time'=>null,'cal'=>$ev['cal']];
            $cur->modify('+1 day'); }
    } else {
        if (!isset($days[$ev['date']])) continue;
        $days[$ev['date']]['events'][]=['summary'=>$ev['summary'],'time'=>$ev['time'],'cal'=>$ev['cal']];
    }
}
foreach ($days as &$day)
    usort($day['events'], fn($a,$b)=>strcmp($a['time']??'ZZ',$b['time']??'ZZ'));
unset($day);

//  Flatten to d0d6 scalar keys  avoids TRMNL Liquid array-iteration issues 
// No nested objects, no arrays  every value is a plain string or int.
// Events are pre-formatted as "HH:MM  Summary" (or just "Summary" if all-day).
$flatDays = array_values($days);
$out = ['generated' => (new DateTime('now', $tz))->format(DateTime::ATOM)];
for ($i = 0; $i < 28; $i++) {
    $day  = $flatDays[$i];
    $evts = $day['events'];
    $ec   = count($evts);
    $out["d{$i}_date"]  = $day['date'];        // "2026-06-02"
    $out["d{$i}_dow"]   = $day['dow'];        // "Sun"  "Sat"
    $out["d{$i}_dom"]   = $day['dom'];         // "17"
    $out["d{$i}_today"] = $day['is_today']   ? 'today' : '';
    $out["d{$i}_wknd"]  = $day['is_weekend'] ? 'wknd'  : '';
    $out["d{$i}_hol"]   = $day['holiday'];     // "" or "Victoria Day"
    // Split into timed events and all-day events
    $timed  = array_values(array_filter($evts, fn($e) => $e['time'] !== null));
    $allday = array_values(array_filter($evts, fn($e) => $e['time'] === null));
    for ($j = 0; $j < 3; $j++) {
        $out["d{$i}_tev{$j}t"] = $timed[$j]['time']    ?? '';  // "07:00"
        $out["d{$i}_tev{$j}n"] = $timed[$j]['summary'] ?? '';  // "422 ZULU Day"
        $out["d{$i}_tev{$j}c"] = $timed[$j]['cal']     ?? '';  // "personal" = BC EHS
    }
    // Pre-compute "more" as a ready-to-display string  empty string when none
    // This lets Liquid use a simple truthy check instead of numeric comparison
    $out["d{$i}_tmore"] = count($timed)  > 3 ? '+' . (count($timed)  - 3) . ' more' : '';
    for ($j = 0; $j < 3; $j++) {
        $out["d{$i}_aev{$j}"] = $allday[$j]['summary'] ?? '';
    }
    $out["d{$i}_amore"] = count($allday) > 3 ? '+' . (count($allday) - 3) . ' more' : '';
}
$output = json_encode($out, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);

$dir=dirname(CACHE_FILE); if(!is_dir($dir)) @mkdir($dir,0755,true);
@file_put_contents(CACHE_FILE,$output,LOCK_EX);

echo $output;
