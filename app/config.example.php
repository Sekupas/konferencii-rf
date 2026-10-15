<?php
return [
    // mysql — основной режим. Для быстрого локального запуска можно выбрать sqlite.
    'driver' => 'mysql',
    'host' => '127.0.0.1',
    'port' => 3306,
    'database' => 'konferencii',
    'username' => 'root',
    'password' => '',
    'sqlite_path' => __DIR__ . '/../var/konferencii.sqlite',
    'timezone' => 'Europe/Moscow',
];
