<?php
// index.php (racine vote_app) - Redirection de sécurité vers public/

declare(strict_types=1);

$queryString = $_SERVER['QUERY_STRING'] ?? '';
$target = 'public/index.php' . ($queryString !== '' ? '?' . $queryString : '');

header('Location: ' . $target, true, 302);
exit;