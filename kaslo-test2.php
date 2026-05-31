<?php
ini_set('display_errors','1'); error_reporting(E_ALL);
register_shutdown_function(function(){
    $e=error_get_last();
    if($e) echo "\nFATAL [{$e['type']}] line {$e['line']}: {$e['message']}";
});

// Check cache files exist
echo "=== Cache files ===\n";
$files = [
    'weather' => __DIR__.'/data/weather-cache.json',
    'dgs'     => __DIR__.'/data/dgs-cache.json',
    'cal'     => __DIR__.'/data/cal-cache-v3.json',
    'todo'    => __DIR__.'/todo.json',
];
foreach ($files as $name => $path) {
    $exists = file_exists($path);
    echo "$name: ".($exists ? 'OK ('.filesize($path).' bytes, age '.round((time()-filemtime($path))/60).'min)' : 'MISSING - '.$path)."\n";
}

// Try running kaslo-api.php
echo "\n=== Running kaslo-api.php ===\n";
$_GET['key']  = 'kw_40e818911c1980bcd56dc4aff37a820f811990a130eb771fd9e21a536edc55ea';
$_GET['game'] = '0';
ob_start();
include __DIR__.'/kaslo-api.php';
$out = ob_get_clean();
echo "Output (".strlen($out)." bytes):\n";
echo substr($out, 0, 300)."\n";
