<?php
// Clear OPcache for kaslo-api.php + delete data caches — delete after debugging
$file = __DIR__ . '/kaslo-api.php';

// OPcache
if (function_exists('opcache_invalidate')) {
    opcache_invalidate($file, true);
    echo "opcache_invalidate: OK\n";
}
if (function_exists('opcache_reset')) {
    opcache_reset();
    echo "opcache_reset: done\n";
}
echo "mtime: " . date('Y-m-d H:i:s', filemtime($file)) . "\n";

// Delete /tmp data caches so they rebuild fresh
$patterns = [
    '/tmp/kaslo4_om.json',      // moon + pressure (most important)
    '/tmp/kaslo4_brd_*.json',   // board positions
];
echo "\n=== Clearing data caches ===\n";
foreach ($patterns as $pat) {
    foreach (glob($pat) ?: [] as $f) {
        unlink($f);
        echo "Deleted: $f\n";
    }
}
echo "Done.\n";
