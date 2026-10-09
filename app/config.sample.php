<?php
return [
    'app' => [
        'url' => 'https://example.com',
        'timezone' => 'Asia/Jakarta',
        'secret_key' => 'CHANGE_THIS_TO_A_32_BYTE_RANDOM_STRING_FOR_PRODUCTION',
    ],
    'db' => [
        'driver' => 'sqlite', // 'mysql' atau 'sqlite'
        'sqlite_path' => __DIR__ . '/storage/sqlite/wpr.sqlite',
        'mysql' => [
            'host' => '127.0.0.1',
            'port' => '3306',
            'database' => 'wpr_db',
            'username' => 'root',
            'password' => '',
            'charset' => 'utf8mb4'
        ]
    ],
    'limits' => [
        'max_file_size_mb' => 2,
        'max_photos_per_guest' => 200,
    ]
];
