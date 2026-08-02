<?php
// Clear OPcache for kaslo-api.php + delete all kaslo_*.json data caches
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

// Delete active caches — kaslo-api.php rebuilds them on next request
$patterns = [
    '/tmp/kaslo_wx.json',
    '/tmp/kaslo_forecast3.json',
    '/tmp/kaslo_ical.json',
    '/tmp/kaslo_dgs_list.json',
    '/tmp/kaslo_dgs_board_*.json',
];
echo "\n=== Clearing data caches ===\n";
foreach ($patterns as $pat) {
    foreach (glob($pat) ?: [] as $f) {
        unlink($f);
        echo "Deleted: $f\n";
    }
}
echo "Done.\n";
