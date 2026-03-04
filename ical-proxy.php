<?php
declare(strict_types=1);

/**
 * ical-proxy.php — CORS proxy for external HTTPS URLs.
 *
 * Auth policy:
 *   - No auth required: anyone can proxy any HTTPS URL.
 *     The only security here is restricting to HTTPS and basic SSRF mitigations.
 *   - The privacy of iCal feeds comes from the feed URLs themselves being secret.
 *   - Weather API calls (EcoWitt, Open-Meteo) also route through here and must
 *     work without a session so the dashboard stays functional after logout.
 */

require __DIR__ . '/auth.php';
// ↑ auth.php is included only so session data is available to callers if needed,
//   but no auth check is enforced on this proxy itself.

$rawUrl = $_GET['url'] ?? '';
$url    = trim($rawUrl);

if ($url === '') {
    http_response_code(400);
    header('Content-Type: text/plain; charset=utf-8');
    echo "Missing url parameter.\n";
    exit;
}

// Only allow https:// to prevent SSRF against plain-HTTP internal services
if (!preg_match('~^https://~i', $url)) {
    http_response_code(400);
    header('Content-Type: text/plain; charset=utf-8');
    echo "Only https:// URLs are allowed.\n";
    exit;
}

if (filter_var($url, FILTER_VALIDATE_URL) === false) {
    http_response_code(400);
    header('Content-Type: text/plain; charset=utf-8');
    echo "Invalid URL.\n";
    exit;
}

// Block fetching private/internal address ranges
$host = parse_url($url, PHP_URL_HOST);
if ($host) {
    // Block localhost and common private ranges
    if (preg_match('/^(localhost|127\.|10\.|192\.168\.|172\.(1[6-9]|2\d|3[01])\.)/i', $host)) {
        http_response_code(403);
        header('Content-Type: text/plain; charset=utf-8');
        echo "Forbidden: internal addresses not allowed.\n";
        exit;
    }
}

// Fetch remote resource
$ctx = stream_context_create([
    'http' => [
        'method'  => 'GET',
        'timeout' => 10,
        'header'  => "User-Agent: KasloDashboard/1.0\r\n",
    ],
]);

$data = @file_get_contents($url, false, $ctx);

if ($data === false) {
    http_response_code(502);
    header('Content-Type: text/plain; charset=utf-8');
    echo "Failed to fetch remote URL.\n";
    exit;
}

// Detect content type from response headers
$respHeaders = $http_response_header ?? [];
$contentType = 'application/octet-stream';
foreach (array_reverse($respHeaders) as $h) {
    if (stripos($h, 'Content-Type:') === 0) {
        $contentType = trim(substr($h, strlen('Content-Type:')));
        break;
    }
}

header('Content-Type: ' . $contentType);
header('Cache-Control: no-store');
echo $data;
