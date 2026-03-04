<?php
declare(strict_types=1);

require __DIR__ . '/auth.php';
auth_require_api();

header('Content-Type: application/json; charset=utf-8');

$todoFile = __DIR__ . '/todo.json';
$action   = $_GET['action'] ?? '';

/**
 * Load the todo list from disk.
 *
 * @return array<int, array<string,mixed>>
 */
function load_todos(string $file): array
{
    if (!is_file($file)) {
        return [];
    }

    $json = @file_get_contents($file);
    if ($json === false) {
        http_response_code(500);
        echo json_encode(['error' => 'Could not read todo.json'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $data = json_decode($json, true);
    if (!is_array($data)) {
        return [];
    }

    return $data;
}

/**
 * Persist the todo list to disk using an atomic write.
 *
 * @param array<int, array<string,mixed>> $items
 */
function save_todos(string $file, array $items): void
{
    $json = json_encode($items, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    if ($json === false) {
        http_response_code(500);
        echo json_encode(['error' => 'Encode failed'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Write to temp file then rename for best-effort atomicity
    $dir = dirname($file);
    $tmp = tempnam($dir, 'todo_');
    if ($tmp === false) {
        http_response_code(500);
        echo json_encode(['error' => 'Could not create temp file'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if (file_put_contents($tmp, $json, LOCK_EX) === false) {
        @unlink($tmp);
        http_response_code(500);
        echo json_encode(['error' => 'Could not write todo data'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if (!@rename($tmp, $file)) {
        @unlink($tmp);
        http_response_code(500);
        echo json_encode(['error' => 'Could not finalize todo save'], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Save updated list
    $raw = file_get_contents('php://input');
    if ($raw === false) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing request body'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $data = json_decode($raw, true);
    if (!is_array($data)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid JSON payload'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Very light validation: ensure each item has an id and text field
    foreach ($data as $item) {
        if (!is_array($item) || !isset($item['id'], $item['text'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid item structure'], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }

    save_todos($todoFile, $data);
    echo json_encode(['count' => count($data)], JSON_UNESCAPED_UNICODE);
    exit;
}

// GET: return list; `action=raw` and default both return the same array
$items = load_todos($todoFile);
echo json_encode($items, JSON_UNESCAPED_UNICODE);

