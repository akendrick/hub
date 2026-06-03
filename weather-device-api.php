<?php
/**
 * weather-device-api.php  Unified weather JSON for TRMNL.
 *
 * Merges EcoWitt + Open-Meteo into one flat, Liquid-friendly response.
 * No parallel arrays  all values are pre-computed strings/objects so
 * TRMNL's Liquid never needs to do IDX.nested_array[i] indexing.
 *
 * Usage: GET /weather-device-api.php?key=YOUR_KEY
 * Cache: 10 minutes (data/weather-cache.json)
 */

const DEVICE_KEY    = 'kw_40e818911c1980bcd56dc4aff37a820f811990a130eb771fd9e21a536edc55ea';
const CACHE_FILE    = __DIR__ . '/data/weather-cache.json';
const CACHE_TTL     = 600;
const FETCH_TIMEOUT = 8;
const WX_TZ         = 'America/Vancouver';

const ECOWITT_URL = 'https://api.ecowitt.net/api/v3/device/real_time'
    . '?application_key=C6FD389063D6A82CC7A68532000A5962'
    . '&api_key=1c2c26a5-a293-4f82-a69a-9e77b6447344'
    . '&mac=E0:5A:1B:21:11:57&call_back=all'
    . '&temp_unitid=1&pressure_unitid=3&wind_speed_unitid=7'
    . '&rainfall_unitid=12&solar_irradiance_unitid=16';

const OPENMETEO_URL = 'https://api.open-meteo.com/v1/forecast'
    . '?latitude=49.912&longitude=-116.908'
    . '&current=temperature_2m,apparent_temperature,wind_speed_10m,'
    . 'wind_direction_10m,wind_gusts_10m,relative_humidity_2m,'
    . 'surface_pressure,uv_index,weather_code'
    . '&daily=temperature_2m_max,temperature_2m_min,precipitation_sum,'
    . 'precipitation_probability_max,weather_code,sunrise,sunset'
    . '&wind_speed_unit=kmh&timezone=America%2FVancouver&forecast_days=7';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$supplied = trim($_GET['key'] ?? '');
if ($supplied === '' || !hash_equals(DEVICE_KEY, $supplied)) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Serve from cache if fresh
if (file_exists(CACHE_FILE) && (time() - filemtime(CACHE_FILE)) < CACHE_TTL) {
    $cached = @file_get_contents(CACHE_FILE);
    if ($cached && strlen($cached) > 10) { echo $cached; exit; }
}

function wx_fetch(string $url): ?array {
    $ctx = stream_context_create(['http' => [
        'timeout'       => FETCH_TIMEOUT,
        'user_agent'    => 'Mozilla/5.0 (compatible; knotwork-weather/2.0)',
        'ignore_errors' => true,
    ]]);
    $body = @file_get_contents($url, false, $ctx);
    if (!$body) return null;
    $d = json_decode($body, true);
    return is_array($d) ? $d : null;
}

function wmo(int $c): string {
    if ($c === 0)                   return 'Clear';
    if ($c === 1)                   return 'Mostly Clear';
    if ($c === 2)                   return 'Partly Cloudy';
    if ($c === 3)                   return 'Overcast';
    if ($c === 45 || $c === 48)     return 'Fog';
    if ($c >= 51 && $c <= 55)       return 'Drizzle';
    if ($c >= 61 && $c <= 65)       return 'Rain';
    if ($c >= 71 && $c <= 75)       return 'Snow';
    if ($c >= 80 && $c <= 82)       return 'Showers';
    if ($c === 85 || $c === 86)     return 'Snow Showers';
    if ($c >= 95)                   return 'Thunderstorm';
    return '';
}

function wdir(float $deg): string {
    return ['N','NE','E','SE','S','SW','W','NW'][(int)round($deg / 45) % 8];
}

function ri(mixed $v, int $dec = 0): string {
    // round + int-cast to clean string, safe for null/missing values
    if ($v === null || $v === '') return '0';
    return $dec > 0 ? number_format((float)$v, $dec) : (string)(int)round((float)$v);
}

$eco = wx_fetch(ECOWITT_URL);
$met = wx_fetch(OPENMETEO_URL);

$out = [];

//  EcoWitt current conditions 
$ed = $eco['data'] ?? [];
$out['temp']        = ri($ed['outdoor']['temperature']['value'] ?? null);
$out['temp_full']   = $ed['outdoor']['temperature']['value'] ?? '';
$out['feels']       = ri($ed['outdoor']['feels_like']['value'] ?? null);
$out['humidity']    = ri($ed['outdoor']['humidity']['value'] ?? null);
$out['dew']         = ri($ed['outdoor']['dew_point']['value'] ?? null);
$out['wind_kmh']    = ri($ed['wind']['wind_speed']['value'] ?? null);
$out['gust_kmh']    = ri($ed['wind']['wind_gust']['value'] ?? null);
$out['wdir']        = wdir((float)($ed['wind']['wind_direction']['value'] ?? 0));
$out['pressure']    = ri($ed['pressure']['relative']['value'] ?? null);
$out['uvi']         = $ed['solar_and_uvi']['uvi']['value'] ?? '0';
$out['solar']       = ri($ed['solar_and_uvi']['solar']['value'] ?? null);
$out['rain_day']    = ri($ed['rainfall']['daily']['value'] ?? null, 1);
$out['rain_rate']   = ri($ed['rainfall']['rain_rate']['value'] ?? null, 1);
$out['rain_week']   = ri($ed['rainfall']['weekly']['value'] ?? null, 1);
$out['indoor_temp'] = ri($ed['indoor']['temperature']['value'] ?? null);
$out['indoor_hum']  = ri($ed['indoor']['humidity']['value'] ?? null);

//  Open-Meteo forecast 
$md = $met['daily'] ?? [];
$mc = $met['current'] ?? [];

$out['condition'] = wmo((int)($mc['weather_code'] ?? 0));
$out['hi']        = ri($md['temperature_2m_max'][0] ?? null);
$out['lo']        = ri($md['temperature_2m_min'][0] ?? null);

// Sunrise / sunset  strip the date prefix ("2026-05-16T05:23"  "05:23")
$srRaw = $md['sunrise'][0] ?? '';
$ssRaw = $md['sunset'][0]  ?? '';
$out['sunrise'] = strlen($srRaw) >= 16 ? substr($srRaw, 11, 5) : '';
$out['sunset']  = strlen($ssRaw) >= 16 ? substr($ssRaw, 11, 5) : '';

// Daylight duration as pre-formatted strings
if ($out['sunrise'] !== '' && $out['sunset'] !== '') {
    [$srH, $srM] = array_map('intval', explode(':', $out['sunrise']));
    [$ssH, $ssM] = array_map('intval', explode(':', $out['sunset']));
    $dayMin = ($ssH * 60 + $ssM) - ($srH * 60 + $srM);
    if ($dayMin < 0) $dayMin += 1440;
    $out['daylight'] = intdiv($dayMin, 60) . 'h ' . str_pad($dayMin % 60, 2, '0', STR_PAD_LEFT) . 'm daylight';
} else {
    $out['daylight'] = '';
}

// 7-day forecast  array of plain objects, no parallel arrays
$tz = new DateTimeZone(WX_TZ);
$forecast = [];
for ($i = 0; $i < 7; $i++) {
    $code    = (int)($md['weather_code'][$i] ?? 0);
    $timeStr = $md['time'][$i] ?? '';
    $dow     = $timeStr ? (DateTime::createFromFormat('Y-m-d', $timeStr, $tz)?->format('D') ?? '') : '';
    $pop     = (int)($md['precipitation_probability_max'][$i] ?? 0);
    $mm      = round((float)($md['precipitation_sum'][$i] ?? 0), 1);
    $forecast[] = [
        'dow'       => $dow,
        'hi'        => ri($md['temperature_2m_max'][$i] ?? null),
        'lo'        => ri($md['temperature_2m_min'][$i] ?? null),
        'condition' => wmo($code),
        'pop'       => $pop,
        'pop_str'   => $pop > 10 ? $pop . '%' : '',
        'mm_str'    => $mm > 0.1 ? $mm . 'mm' : '',
    ];
}
// Flatten forecast  no arrays, plain scalar keys f0_*  f6_*
foreach ($forecast as $i => $fc) {
    $out["f{$i}_dow"]       = $fc['dow'];
    $out["f{$i}_hi"]        = $fc['hi'];
    $out["f{$i}_lo"]        = $fc['lo'];
    $out["f{$i}_condition"] = $fc['condition'];
    $out["f{$i}_pop_str"]   = $fc['pop_str'];
    $out["f{$i}_mm_str"]    = $fc['mm_str'];
}

//  Calendar events for forecast days (reads from cal cache) 
$calCacheFile = __DIR__ . '/data/cal-cache-v3.json';
$calRaw = @file_get_contents($calCacheFile);
if ($calRaw) {
    $calData = json_decode($calRaw, true);
    if (is_array($calData)) {
        for ($i = 0; $i < 7; $i++) {
            $out["f{$i}_hol"]   = $calData["d{$i}_hol"]   ?? '';
            $out["f{$i}_tev0t"] = $calData["d{$i}_tev0t"] ?? '';
            $out["f{$i}_tev0n"] = $calData["d{$i}_tev0n"] ?? '';
            $out["f{$i}_tev1t"] = $calData["d{$i}_tev1t"] ?? '';
            $out["f{$i}_tev1n"] = $calData["d{$i}_tev1n"] ?? '';
            $out["f{$i}_tmore"] = $calData["d{$i}_tmore"] ?? '';
            $out["f{$i}_aev0"]  = $calData["d{$i}_aev0"]  ?? '';
            $out["f{$i}_aev1"]  = $calData["d{$i}_aev1"]  ?? '';
            $out["f{$i}_amore"] = $calData["d{$i}_amore"] ?? '';
        }
    }
}

$json = json_encode($out, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

$dir = dirname(CACHE_FILE);
if (!is_dir($dir)) @mkdir($dir, 0755, true);
@file_put_contents(CACHE_FILE, $json, LOCK_EX);

echo $json;
