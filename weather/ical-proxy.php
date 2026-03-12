<?php
/**
 * Lightweight proxy for Raspberry Pi weather display
 */

$ALLOWED_HOSTS = [
    'api.weather.com',
    'api.open-meteo.com'
];

header('Access-Control-Allow-Origin: *');
header('Cache-Control: public, max-age=600'); // 10 minutes

if (!isset($_GET['url'])) {
    http_response_code(400);
    exit('Missing url parameter');
}

$url = $_GET['url'];
$parts = parse_url($url);

if (!$parts || empty($parts['host']) || empty($parts['scheme'])) {
    http_response_code(400);
    exit('Invalid URL');
}

if ($parts['scheme'] !== 'https') {
    http_response_code(403);
    exit('HTTPS only');
}

if (!in_array($parts['host'], $ALLOWED_HOSTS)) {
    http_response_code(403);
    exit('Host not allowed');
}

$context = stream_context_create([
    'http' => [
        'timeout' => 6,
        'user_agent' => 'KasloPiWeather/1.0'
    ]
]);

$response = @file_get_contents($url, false, $context);

if ($response === false) {
    http_response_code(502);
    exit('Fetch failed');
}

if (strpos($parts['host'], 'weather.com') !== false) {
    header('Content-Type: application/json; charset=utf-8');
} else {
    header('Content-Type: application/json; charset=utf-8');
}

echo $response;