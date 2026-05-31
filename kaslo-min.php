<?php
header('Content-Type: application/json');
echo json_encode([
    'status' => 'ok',
    'php'    => PHP_VERSION,
    'time'   => date('Y-m-d H:i:s'),
]);
