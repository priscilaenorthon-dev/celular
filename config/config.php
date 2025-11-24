<?php
// Basic configuration for database and global settings
return [
    'db' => [
        'host' => '127.0.0.1',
        'name' => 'celular_rentals',
        'user' => 'root',
        'pass' => '',
        'charset' => 'utf8mb4',
    ],
    'app' => [
        'base_url' => '/celular',
        'upload_dir' => __DIR__ . '/../uploads',
        'upload_url' => '/celular/uploads',
    ],
];
