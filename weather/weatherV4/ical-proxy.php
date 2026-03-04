<?php
declare(strict_types=1);

require __DIR__ . '/auth.php';
auth_require_api();

$rawUrl = $_GET['url'] ?? '';
$url    = trim($rawUrl);

if ($url === '') {
    http_response_code(400);
    header('Content-Type: text/plain; charset=utf-8');
    echo "Missing url parameter.\n";
    exit;
}

// Basic validation: only allow https URLs
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

// Fetch remote calendar with a short timeout
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
    echo "Failed to fetch remote calendar.\n";
    exit;
}

// Pass through as iCalendar text
header('Content-Type: text/calendar; charset=utf-8');
echo $data;

