<?php
/**
 * cal-device-api.php — 14-day calendar JSON for TRMNL and remote devices.
 *
 * Usage:  GET /cal-device-api.php?key=YOUR_DEVICE_KEY
 *
 * Returns JSON:
 * {
 *   "days": [
 *     {
 *       "date":         "2026-05-14",
 *       "dow":          "Thu",
 *       "dom":          "14",
 *       "is_today":     true,
 *       "is_weekend":   false,
 *       "holiday":      "",          // BC holiday name, or ""
 *       "events": [
 *         { "summary": "Dentist", "time": "10:00", "cal": "personal" }
 *       ]
 *     }, …  (14 items total, today through today+13)
 *   ],
 *   "generated": "2026-05-14T10:30:00-07:00"
 * }
 *
 * ── Setup ─────────────────────────────────────────────────────────────────────
 * 1. Set DEVICE_KEY below.
 * 2. Confirm the iCal URLs in $CALS still match your published calendars.
 * 3. Drop this file next to kaslo-weather.php.
 * ─────────────────────────────────────────────────────────────────────────────
 */

// ── Config ────────────────────────────────────────────────────────────────────

const DEVICE_KEY = 'kw_40e818911c1980bcd56dc4aff37a820f811990a130eb771fd9e21a536edc55ea';

/** How many days ahead to include (today = day 0). */
const WINDOW_DAYS = 14;

/** Timeout for each iCal fetch in seconds. */
const FETCH_TIMEOUT = 8;

/**
 * Calendar feeds.  label = used in JSON cal field; shown as source hint.
 */
$CALS = [
    ['url' => 'https://p162-caldav.icloud.com/published/2/MTI5MzgzNTk0MTI5MzgzNWY9cTYgK0OwIRz4UCfQYKvJY44bQNHC73gDwS5V1U5jUkV9ynvy7uipCHeNIfenut1Eq0LNCaulcS6IDwnCXzpP_iBTsC0Fc21d3SMFtSvB_1CnVBymPyAyPnzFyGkhsg', 'label' => 'personal'],
    ['url' => 'https://p102-caldav.icloud.com/published/2/MTI5MzgzNTk0MTI5MzgzNWY9cTYgK0OwIRz4UCfQYKvnN6NvIE36DmOUbrBgiLcGN7ezhsYo-YXFxAw38AN_vpaiEFRiXefJROr9Az80VcU', 'label' => 'personal2'],
    ['url' => 'https://p101-caldav.icloud.com/published/2/Mjc4Mjk1ODMxMjc4Mjk1OPiLHZnPp67Ltgtp3v229x8qT-uPdlC-Sg6bv_JZdLUiimpxJVvfu-OL9CBtnZ3CMevVIgwICabIi9WTyZIKqHA', 'label' => 'personal3'],
];

// ── BC Holidays ───────────────────────────────────────────────────────────────
// Hardcoded map for the current year; update annually or replace with a fetch.
const BC_HOLIDAYS = [
    '2026-01-01' => "New Year's Day",
    '2026-02-16' => 'Family Day',
    '2026-04-03' => 'Good Friday',
    '2026-05-18' => 'Victoria Day',
    '2026-07-01' => 'Canada Day',
    '2026-08-03' => 'British Columbia Day',
    '2026-09-07' => 'Labour Day',
    '2026-09-30' => 'Truth & Reconciliation Day',
    '2026-10-12' => 'Thanksgiving',
    '2026-11-11' => 'Remembrance Day',
    '2026-12-25' => 'Christmas Day',
];

// ── Headers ───────────────────────────────────────────────────────────────────
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('X-Content-Type-Options: nosniff');

// ── Auth ──────────────────────────────────────────────────────────────────────
$supplied = trim($_GET['key'] ?? '');
if ($supplied === '' || !hash_equals(DEVICE_KEY, $supplied)) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// ── Helpers: iCal parser ──────────────────────────────────────────────────────

/**
 * Fetch a remote URL with a timeout and a browser-like User-Agent.
 * iCloud requires a non-empty UA or it returns 403.
 */
function fetch_url(string $url): ?string {
    $ctx = stream_context_create(['http' => [
        'timeout'    => FETCH_TIMEOUT,
        'user_agent' => 'Mozilla/5.0 (compatible; TRMNL-cal-proxy/1.0)',
        'ignore_errors' => true,
    ]]);
    $body = @file_get_contents($url, false, $ctx);
    if ($body === false || strpos($body, 'BEGIN:VCALENDAR') === false) return null;
    return $body;
}

/**
 * Unfold iCal continuation lines (RFC 5545 §3.1).
 */
function ical_unfold(string $text): string {
    return preg_replace('/\r?\n[ \t]/', '', $text);
}

/**
 * Parse a DTSTART/DTEND value string.
 * Returns ['date'=>'YYYY-MM-DD', 'time'=>'HH:MM' or null, 'allday'=>bool]
 */
function parse_dt(string $val): array {
    // Strip TZID= param if present (comes before the colon in the raw line —
    // by the time we're here we've already split on ':')
    $val = preg_replace('/^[A-Z\/+_-]+:/', '', $val); // strip any leftover param
    $val = trim($val);

    // All-day: 20250224
    if (preg_match('/^(\d{4})(\d{2})(\d{2})$/', $val, $m)) {
        return ['date' => "$m[1]-$m[2]-$m[3]", 'time' => null, 'allday' => true];
    }

    // DateTime: 20250224T150000Z or 20250224T150000
    if (preg_match('/^(\d{4})(\d{2})(\d{2})T(\d{2})(\d{2})/', $val, $m)) {
        $isUtc = str_ends_with(trim($val), 'Z');
        $h = (int)$m[4]; $mn = (int)$m[5];
        if ($isUtc) {
            // Convert UTC → America/Vancouver
            try {
                $dt = new DateTime($val, new DateTimeZone('UTC'));
                $dt->setTimezone(new DateTimeZone('America/Vancouver'));
                return [
                    'date'   => $dt->format('Y-m-d'),
                    'time'   => $dt->format('H:i'),
                    'allday' => false,
                ];
            } catch (Exception $e) {
                // Fall through to naive
            }
        }
        return [
            'date'   => "$m[1]-$m[2]-$m[3]",
            'time'   => sprintf('%02d:%02d', $h, $mn),
            'allday' => false,
        ];
    }

    return ['date' => null, 'time' => null, 'allday' => true];
}

/**
 * Parse raw iCal text → array of event arrays.
 * Each event: ['date'=>..., 'end_date'=>..., 'summary'=>..., 'time'=>..., 'allday'=>bool]
 */
function parse_ical(string $raw, string $calLabel): array {
    $text   = ical_unfold($raw);
    $blocks = preg_split('/BEGIN:VEVENT/', $text);
    $events = [];

    for ($i = 1; $i < count($blocks); $i++) {
        $block   = $blocks[$i];
        $lines   = preg_split('/\r?\n/', $block);
        $summary = '';
        $dtstart = null;
        $dtend   = null;

        foreach ($lines as $line) {
            $upper = strtoupper($line);
            if (str_starts_with($upper, 'SUMMARY:')) {
                $s = substr($line, strpos($line, ':') + 1);
                $summary = strtr(trim($s), ['\\n' => ' ', '\\,' => ',', '\\;' => ';', '\\\\' => '\\']);
            }
            if (str_starts_with($upper, 'DTSTART')) {
                $raw_val = substr($line, strpos($line, ':') + 1);
                $dtstart = parse_dt(trim($raw_val));
            }
            if (str_starts_with($upper, 'DTEND') || str_starts_with($upper, 'DUE')) {
                $raw_val = substr($line, strpos($line, ':') + 1);
                $dtend   = parse_dt(trim($raw_val));
                // iCal all-day DTEND is exclusive (day after last day) — subtract 1
                if ($dtend && $dtend['allday'] && $dtend['date']) {
                    $d = new DateTime($dtend['date'] . 'T12:00:00');
                    $d->modify('-1 day');
                    $dtend['date'] = $d->format('Y-m-d');
                }
            }
        }

        if ($summary && $dtstart && $dtstart['date']) {
            $endDate = ($dtend && $dtend['date']) ? $dtend['date'] : $dtstart['date'];
            $events[] = [
                'summary'    => $summary,
                'date'       => $dtstart['date'],
                'end_date'   => $endDate,
                'time'       => $dtstart['time'],    // 'HH:MM' or null for all-day
                'allday'     => $dtstart['allday'],
                'multi_day'  => ($endDate > $dtstart['date']),
                'cal'        => $calLabel,
            ];
        }
    }

    return $events;
}

// ── Build 14-day window ───────────────────────────────────────────────────────

$tz    = new DateTimeZone('America/Vancouver');
$today = new DateTime('today', $tz);

// Build day scaffolding
$days = [];
for ($i = 0; $i < WINDOW_DAYS; $i++) {
    $d    = clone $today;
    $d->modify("+$i days");
    $date = $d->format('Y-m-d');
    $dow  = $d->format('D');   // Mon, Tue, …
    $dom  = $d->format('j');   // 1-31
    $days[$date] = [
        'date'       => $date,
        'dow'        => $dow,
        'dom'        => $dom,
        'is_today'   => ($i === 0),
        'is_weekend' => in_array($d->format('N'), ['6','7']),
        'holiday'    => BC_HOLIDAYS[$date] ?? '',
        'events'     => [],
    ];
}
$windowStart = array_key_first($days);
$windowEnd   = array_key_last($days);

// ── Fetch + parse calendars ───────────────────────────────────────────────────
global $CALS;
$allEvents = [];
foreach ($CALS as $cal) {
    $raw = fetch_url($cal['url']);
    if ($raw === null) continue;
    $parsed = parse_ical($raw, $cal['label']);
    foreach ($parsed as $ev) {
        $allEvents[] = $ev;
    }
}

// ── Distribute events into days ───────────────────────────────────────────────
// Multi-day events appear on every day they span (within the window).
$seen = [];
foreach ($allEvents as $ev) {
    $key = $ev['summary'] . '|' . $ev['date'];
    if (isset($seen[$key])) continue;
    $seen[$key] = true;

    if ($ev['multi_day']) {
        // Clamp span to window
        $spanStart = max($ev['date'],     $windowStart);
        $spanEnd   = min($ev['end_date'], $windowEnd);
        if ($spanStart > $windowEnd || $spanEnd < $windowStart) continue;
        $cur = new DateTime($spanStart . 'T12:00:00', $tz);
        $end = new DateTime($spanEnd   . 'T12:00:00', $tz);
        while ($cur <= $end) {
            $d = $cur->format('Y-m-d');
            if (isset($days[$d])) {
                $days[$d]['events'][] = [
                    'summary' => $ev['summary'],
                    'time'    => null,
                    'cal'     => $ev['cal'],
                ];
            }
            $cur->modify('+1 day');
        }
    } else {
        if (!isset($days[$ev['date']])) continue;
        $days[$ev['date']]['events'][] = [
            'summary' => $ev['summary'],
            'time'    => $ev['time'],  // 'HH:MM' or null
            'cal'     => $ev['cal'],
        ];
    }
}

// Sort events within each day: timed events first (by time), then all-day
foreach ($days as &$day) {
    usort($day['events'], function ($a, $b) {
        $ta = $a['time'] ?? 'ZZ';  // nulls sort last
        $tb = $b['time'] ?? 'ZZ';
        return strcmp($ta, $tb);
    });
}
unset($day);

// ── Output ────────────────────────────────────────────────────────────────────
echo json_encode([
    'days'      => array_values($days),
    'generated' => (new DateTime('now', $tz))->format(DateTime::ATOM),
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
