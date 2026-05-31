<?php
$file = __DIR__ . '/kaslo-api.php';
if (function_exists('opcache_invalidate')) {
    $ok = opcache_invalidate($file, true);
    echo "opcache_invalidate: " . ($ok ? 'OK' : 'failed') . "\n";
} else {
    echo "opcache not available\n";
}
if (function_exists('opcache_reset')) {
    opcache_reset();
    echo "opcache_reset: done\n";
}
echo "mtime: " . date('Y-m-d H:i:s', filemtime($file)) . "\n";
echo "size: " . filesize($file) . " bytes\n";
