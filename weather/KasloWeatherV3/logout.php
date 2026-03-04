<?php
declare(strict_types=1);

require __DIR__ . '/auth.php';

auth_logout();

$redirect = $_GET['redirect'] ?? '/login.php';
header('Location: ' . $redirect);
exit;

