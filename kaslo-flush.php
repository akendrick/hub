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
    '/tmp/kaslo4_om.json',      // moon + pressure
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

// Rebuild cal cache (14 days) by calling cal-device-api.php directly
echo "\n=== Rebuilding calendar cache (14 days) ===\n";
$cal_url = 'https://knotwork.ca/cal-device-api.php?key=kw_40e818911c1980bcd56dc4aff37a820f811990a130eb771fd9e21a536edc55ea';
$ctx = stream_context_create(['http'=>['timeout'=>20,'user_agent'=>'kaslo-flush/1.0','ignore_errors'=>true]]);
$cal_result = @file_get_contents($cal_url, false, $ctx);
if ($cal_result && str_contains($cal_result, 'd0_dow')) {
    $d = json_decode($cal_result, true);
    // Count how many days returned
    $days = 0;
    for ($i=0; $i<14; $i++) { if (isset($d["d{$i}_dow"])) $days++; else break; }
    echo "Calendar rebuilt: $days days (d0={$d['d0_dow']} {$d['d0_dom']})\n";
} else {
    echo "Calendar rebuild failed or returned unexpected data\n";
}
