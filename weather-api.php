<?php
/**
 * weather-api.php — Weather, sun, and moon data for the iOS app.
 *
 * Authentication: device API key (same as device-api.php)
 *   Authorization: Bearer kw_<64hex>
 *   OR: ?api_key=kw_<64hex>
 *
 * Response (JSON):
 * {
 *   "obs":      { current station observations },
 *   "forecast": [ 16 days of daily forecast ],
 *   "sun":      { today's sunrise/sunset/daylight + sun position angle },
 *   "moon":     { phase 0–1, name, illumination 0–1, emoji }
 *   "meta":     { obs_age_s, forecast_age_s, generated }
 * }
 *
 * All values use metric units matching the EcoWitt / Open-Meteo feeds.
 * Fields are null when data is unavailable rather than omitted.
 */
declare(strict_types=1);

require __DIR__ . '/auth.php';
auth_require_device_api();

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

const CACHE_DIR = __DIR__ . '/cache/';

// ── Cache helpers ──────────────────────────────────────────────────────────

function read_cache(string $key): ?array
{
    $file = CACHE_DIR . $key . '.json';
    if (!is_file($file)) return null;
    $raw = @file_get_contents($file);
    if (!$raw) return null;
    $data = json_decode($raw, true);
    if (!is_array($data)) return null;
    return ['data' => $data, 'ts' => (int)filemtime($file), 'age' => time() - (int)filemtime($file)];
}

// ── Moon phase ─────────────────────────────────────────────────────────────
// Uses a known new moon epoch and the mean synodic period.

function moon_phase(?\DateTimeImmutable $dt = null): array
{
    $dt   ??= new DateTimeImmutable('now', new DateTimeZone('UTC'));
    // Known new moon: 2000-01-06 18:14 UTC  (J2000.0 era reference)
    $knownNewMoon = new DateTimeImmutable('2000-01-06 18:14:00', new DateTimeZone('UTC'));
    $synodic      = 29.530588853; // mean synodic period in days

    $daysSince = ($dt->getTimestamp() - $knownNewMoon->getTimestamp()) / 86400.0;
    $phase     = fmod($daysSince / $synodic, 1.0);
    if ($phase < 0) $phase += 1.0;

    // Illumination: cos-based approximation (0 at new, 1 at full)
    $illumination = (1 - cos($phase * 2 * M_PI)) / 2;

    // Phase name buckets
    $pct = $phase * 100;
    if      ($pct <  3.5)  $name = 'New Moon';
    elseif  ($pct < 10.0)  $name = 'Waxing Crescent';
    elseif  ($pct < 15.0)  $name = 'First Quarter';
    elseif  ($pct < 35.0)  $name = 'Waxing Gibbous';
    elseif  ($pct < 38.5)  $name = 'Full Moon';
    elseif  ($pct < 63.0)  $name = 'Waning Gibbous';
    elseif  ($pct < 67.5)  $name = 'Last Quarter';
    elseif  ($pct < 96.5)  $name = 'Waning Crescent';
    else                    $name = 'New Moon';

    // Emoji
    $segment = (int)round($phase * 8) % 8;
    $emojis  = ['🌑','🌒','🌓','🌔','🌕','🌖','🌗','🌘'];
    $emoji   = $emojis[$segment];

    return [
        'phase'        => round($phase, 4),
        'name'         => $name,
        'illumination' => round($illumination, 3),
        'emoji'        => $emoji,
    ];
}

// ── Sun position (angle 0–360, noon at top = 180) ─────────────────────────

function sun_info(?string $sunriseIso, ?string $sunsetIso): array
{
    $out = [
        'sunrise'         => $sunriseIso,
        'sunset'          => $sunsetIso,
        'daylight_minutes'=> null,
        'now_angle'       => null,   // 0-360: position of sun on dial (noon=180)
        'is_daytime'      => null,
    ];

    if (!$sunriseIso || !$sunsetIso) return $out;

    try {
        $tz  = new DateTimeZone('America/Vancouver');
        $sr  = new DateTimeImmutable($sunriseIso, $tz);
        $ss  = new DateTimeImmutable($sunsetIso,  $tz);
        $now = new DateTimeImmutable('now', $tz);

        $srM = (int)$sr->format('G') * 60 + (int)$sr->format('i');
        $ssM = (int)$ss->format('G') * 60 + (int)$ss->format('i');
        $nowM = (int)$now->format('G') * 60 + (int)$now->format('i');

        $out['daylight_minutes'] = $ssM - $srM;
        $out['is_daytime']       = ($nowM >= $srM && $nowM <= $ssM);

        // Angle: midnight = 0°, noon = 180°, 1440 mins/day
        $out['now_angle'] = round(($nowM / 1440) * 360, 1);
    } catch (\Throwable) {}

    return $out;
}

// ── Observations ───────────────────────────────────────────────────────────

function build_obs(?array $cache): array
{
    $d = $cache['data'] ?? null;
    if (!$d) return array_fill_keys([
        'temp','feels_like','dew_point','humidity','pressure',
        'wind_speed','wind_direction','wind_gust',
        'precip_rate','precip_24h','precip_7d',
        'uv','solar_wm2','indoor_temp','indoor_humidity'
    ], null);

    return [
        'temp'             => isset($d['temp'])        ? (float)$d['temp']        : null,
        'feels_like'       => isset($d['heatIndex'])   ? (float)$d['heatIndex']   : null,
        'dew_point'        => isset($d['dewpt'])        ? (float)$d['dewpt']        : null,
        'humidity'         => isset($d['humidity'])    ? (int)$d['humidity']      : null,
        'pressure_hpa'     => isset($d['pressure'])    ? (float)$d['pressure']    : null,
        'wind_speed_kmh'   => isset($d['windSpeed'])   ? (float)$d['windSpeed']   : null,
        'wind_direction'   => isset($d['windDir'])     ? (int)$d['windDir']       : null,
        'wind_gust_kmh'    => isset($d['windGust'])    ? (float)$d['windGust']    : null,
        'precip_rate_mmh'  => isset($d['precipRate'])  ? (float)$d['precipRate']  : null,
        'precip_24h_mm'    => isset($d['precip24h'])   ? (float)$d['precip24h']   : null,
        'precip_7d_mm'     => isset($d['precip30d'])   ? (float)$d['precip30d']   : null,
        'uv_index'         => isset($d['uv'])          ? (float)$d['uv']          : null,
        'solar_wm2'        => isset($d['solar'])       ? (float)$d['solar']       : null,
        'indoor_temp'      => isset($d['indoorTemp'])  ? (float)$d['indoorTemp']  : null,
        'indoor_humidity'  => isset($d['indoorHum'])   ? (int)$d['indoorHum']     : null,
    ];
}

// ── Forecast ───────────────────────────────────────────────────────────────

function wmo_icon(int $code): string
{
    if (in_array($code, [95,96,99])) return '⛈';
    if (in_array($code, [71,73,75,77,85,86])) return '❄️';
    if (in_array($code, [66,67])) return '🌧';
    if (in_array($code, [61,63,65,80,81,82])) return '🌧';
    if (in_array($code, [51,53,55])) return '🌦';
    if (in_array($code, [45,48])) return '🌫';
    if ($code === 3) return '☁️';
    if ($code === 2) return '⛅';
    if ($code === 1) return '🌤';
    if ($code === 0) return '☀️';
    return '—';
}

function wmo_desc(int $code): string
{
    if (in_array($code, [95,96,99])) return 'Thunderstorm';
    if (in_array($code, [71,73,75,77,85,86])) return 'Snow';
    if (in_array($code, [66,67])) return 'Freezing Rain';
    if (in_array($code, [61,63,65,80,81,82])) return 'Rain';
    if (in_array($code, [51,53,55])) return 'Drizzle';
    if (in_array($code, [45,48])) return 'Fog';
    if ($code === 3) return 'Overcast';
    if ($code === 2) return 'Partly Cloudy';
    if ($code === 1) return 'Mostly Clear';
    if ($code === 0) return 'Clear';
    return '';
}

function build_forecast(?array $cache): array
{
    $daily = $cache['data']['daily'] ?? null;
    if (!$daily) return [];

    $days = min(count($daily['time'] ?? []), 16);
    $out  = [];
    for ($i = 0; $i < $days; $i++) {
        $code = (int)($daily['weathercode'][$i] ?? 0);
        $out[] = [
            'date'           => $daily['time'][$i]                              ?? null,
            'hi'             => isset($daily['temperature_2m_max'][$i])   ? round((float)$daily['temperature_2m_max'][$i], 1) : null,
            'lo'             => isset($daily['temperature_2m_min'][$i])   ? round((float)$daily['temperature_2m_min'][$i], 1) : null,
            'precip_mm'      => isset($daily['precipitation_sum'][$i])    ? round((float)$daily['precipitation_sum'][$i], 1)  : null,
            'precip_prob_pct'=> isset($daily['precipitation_probability_max'][$i]) ? (int)$daily['precipitation_probability_max'][$i] : null,
            'weather_code'   => $code,
            'icon'           => wmo_icon($code),
            'description'    => wmo_desc($code),
            'sunrise'        => $daily['sunrise'][$i]  ?? null,
            'sunset'         => $daily['sunset'][$i]   ?? null,
        ];
    }
    return $out;
}

// ── Assemble response ──────────────────────────────────────────────────────

$obsCache      = read_cache('obs');
$forecastCache = read_cache('forecast');

$forecastDays  = build_forecast($forecastCache);
$todaySunrise  = $forecastDays[0]['sunrise'] ?? null;
$todaySunset   = $forecastDays[0]['sunset']  ?? null;

$response = [
    'obs'      => build_obs($obsCache),
    'forecast' => $forecastDays,
    'sun'      => sun_info($todaySunrise, $todaySunset),
    'moon'     => moon_phase(),
    'meta'     => [
        'obs_age_s'      => $obsCache      ? $obsCache['age']      : null,
        'forecast_age_s' => $forecastCache ? $forecastCache['age'] : null,
        'generated'      => date('c'),
        'timezone'       => 'America/Vancouver',
        'units'          => [
            'temperature'  => '°C',
            'wind'         => 'km/h',
            'pressure'     => 'hPa',
            'precipitation'=> 'mm',
            'solar'        => 'W/m²',
        ],
    ],
];

echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
