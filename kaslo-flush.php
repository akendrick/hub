<?php
// Clear OPcache for kaslo-api.php + delete data caches  delete after debugging
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

// Delete all caches so they rebuild fresh
$patterns = [
    '/tmp/kaslo4_om.json',                     // kaslo-api.php: moon + pressure
    '/tmp/kaslo4_brd_*.json',                  // kaslo-api.php: board positions
    __DIR__ . '/data/weather-cache.json',      // weather-device-api.php  7-day forecast
    __DIR__ . '/data/cal-cache-v3.json',       // cal-device-api.php  28-day calendar
    __DIR__ . '/data/dgs-cache.json',          // dgs-device-api.php  DGS game list
];
echo "\n=== Clearing data caches ===\n";
foreach ($patterns as $pat) {
    foreach (glob($pat) ?: [] as $f) {
        unlink($f);
        echo "Deleted: $f\n";
    }
}
echo "Done.\n";

// Rebuild weather cache by calling weather-device-api.php directly
echo "\n=== Rebuilding weather cache (7 days) ===\n";
$wx_url = 'https://knotwork.ca/weather-device-api.php?key=kw_40e818911c1980bcd56dc4aff37a820f811990a130eb771fd9e21a536edc55ea';
$ctx2 = stream_context_create(['http'=>['timeout'=>20,'user_agent'=>'kaslo-flush/1.0','ignore_errors'=>true]]);
$wx_result = @file_get_contents($wx_url, false, $ctx2);
if ($wx_result && str_contains($wx_result, 'f0_hi')) {
    $wd = json_decode($wx_result, true);
    $days = 0;
    for ($i=0; $i<=6; $i++) { if (!empty($wd["f{$i}_hi"])) $days++; else break; }
    echo "Weather rebuilt: $days forecast days (today hi={$wd['f0_hi']} lo={$wd['f0_lo']})\n";
} else {
    echo "Weather rebuild failed\n";
}

// Rebuild cal cache (14 days) by calling cal-device-api.php directly
echo "\n=== Rebuilding calendar cache (28 days) ===\n";
$cal_url = 'https://knotwork.ca/cal-device-api.php?key=kw_40e818911c1980bcd56dc4aff37a820f811990a130eb771fd9e21a536edc55ea';
$ctx = stream_context_create(['http'=>['timeout'=>20,'user_agent'=>'kaslo-flush/1.0','ignore_errors'=>true]]);
$cal_result = @file_get_contents($cal_url, false, $ctx);
if ($cal_result && str_contains($cal_result, 'd0_dow')) {
    $d = json_decode($cal_result, true);
    // Count how many days returned
    $days = 0;
    for ($i=0; $i<28; $i++) { if (isset($d["d{$i}_dow"])) $days++; else break; }
    echo "Calendar rebuilt: $days days (d0={$d['d0_dow']} {$d['d0_dom']})\n";
} else {
    echo "Calendar rebuild failed or returned unexpected data\n";
}
