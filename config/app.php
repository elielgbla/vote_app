<?php
// config/app.php

$basePath = dirname(__DIR__);

return [
    'app_name'     => 'VoteApp MVC',
    'base_path'    => $basePath,
    'public_path'  => $basePath . DIRECTORY_SEPARATOR . 'public',
    'storage_path' => $basePath . DIRECTORY_SEPARATOR . 'storage',
    'uploads_dir'  => $basePath . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'candidats',
    'logs_dir'     => $basePath . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'logs',

    // URL de base adaptée à un hébergement sous /vote_app/public ou virtual host
    'base_url'     => '/vote_app/public',

    // Sécurité & uploads d'images candidats
    'session_name' => 'VOTEAPP_SESSID',
    'max_photo_mb' => 2,
    'allowed_mime' => [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
    ],
];