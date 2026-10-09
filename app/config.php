<?php
return [
    'app' => [
        'url' => 'https://vincentiusalfredo.com/kamerawedding',
        'timezone' => 'Asia/Jakarta',
        'secret_key' => '2d4d50252937121acded31c0b9685199fafc191b4ef3726af5afe94d39485f1d',
    ],
    'db' => [
        'driver' => 'mysql', // ← diubah dari 'sqlite'
        'sqlite_path' => __DIR__ . '/storage/sqlite/wpr.sqlite',
        'mysql' => [
            'host' => 'localhost',
            'port' => '3306',
            'database' => 'vinq9912_wpr_db', // ← nama DB cPanel
            'username' => 'vinq9912_admin',  // ← perhatikan prefix!
            'password' => '@marsha12345',
            'charset' => 'utf8mb4'
        ]
    ],
    'limits' => [
        'max_file_size_mb' => 2,
        'max_photos_per_guest' => 200,
    ]
];