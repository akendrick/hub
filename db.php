<?php
/**
 * db.php — PDO connection for knotworkdb
 *
 * !! IMPORTANT !!
 * Replace DB_PASS below with your actual database password before uploading.
 * The placeholder 'FILL_IN_DB_PASSWORD_HERE' will cause a connection error.
 */
declare(strict_types=1);

define('DB_HOST', 'mysql.knotwork.ca');
define('DB_NAME', 'knotworkdb');
define('DB_USER', 'cerrick_dbhub');
define('DB_PASS', 't2_Ea.No.F4sgRiKPmbC');   // <-- REPLACE THIS

function db(): PDO
{
    static $pdo = null;
    if ($pdo !== null) return $pdo;

    if (DB_PASS === 'FILL_IN_DB_PASSWORD_HERE') {
        throw new RuntimeException(
            'db.php: DB_PASS has not been set. Open db.php and replace the placeholder with your real database password.'
        );
    }

    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
    return $pdo;
}
