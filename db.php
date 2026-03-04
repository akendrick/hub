<?php
/**
 * db.php — PDO connection for knotworkdb
 *
 * ⚠  Fill in DB_PASS before deploying.
 */
declare(strict_types=1);

define('DB_HOST', 'mysql.knotwork.ca');
define('DB_NAME', 'knotworkdb');
define('DB_USER', 'cerrick_dbhub');
define('DB_PASS', 't2_Ea.No.F4sgRiKPmbC');   // ← replace this

function db(): PDO
{
    static $pdo = null;
    if ($pdo !== null) return $pdo;

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
