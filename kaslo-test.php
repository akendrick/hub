<?php
// Quick syntax + error check for kaslo-api.php — delete after debugging
$file = __DIR__ . '/kaslo-api.php';

if (!file_exists($file)) {
    echo "NOT FOUND at: $file";
    exit;
}

// Try php -l via shell (shows parse errors)
$lint = shell_exec('php -l ' . escapeshellarg($file) . ' 2>&1');
if ($lint) {
    echo "LINT: $lint\n";
}

// Capture any output/errors from including the file
ini_set('display_errors', '1');
error_reporting(E_ALL);
ob_start();
$_GET['key'] = 'kw_40e818911c1980bcd56dc4aff37a820f811990a130eb771fd9e21a536edc55ea';
$_GET['game'] = '0';
include $file;
$out = ob_get_clean();
echo $out ?: '(no output from include)';
