<?php
// public/index.php

declare(strict_types=1);

$config  = require dirname(__DIR__) . '/config/app.php';
$actions = require dirname(__DIR__) . '/config/actions.php';

session_name($config['session_name']);
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

spl_autoload_register(function (string $class) use ($config): void {
    foreach (['core', 'models', 'controllers'] as $dir) {
        $file = $config['base_path'] . '/' . $dir . '/' . $class . '.php';
        if (is_file($file)) {
            require_once $file;
            return;
        }
    }
});

$pdo         = Database::getConnection();
$authManager = new AuthManager($pdo);
$logger      = new AuditLogger($pdo);
$router      = new Router($config, $actions, $authManager, $logger);

$action = isset($_GET['action']) ? trim((string) $_GET['action']) : 'public.ecran';
$router->dispatch($action);