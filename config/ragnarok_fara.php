<?php

return [
    'fara_remote' => [
        'driver' => 'pgsql',
        'host' => env('FARA_REM_DB_HOST', '127.0.0.1'),
        'port' => env('FARA_REM_DB_PORT', '5432'),
        'database' => env('FARA_REM_DB_DB'),
        'username' => env('FARA_REM_DB_USER'),
        'password' => env('FARA_REM_DB_PASS'),
        'charset' => 'utf8',
        'prefix' => '',
        'prefix_indexes' => true,
        'schema' => 'public',
        'sslmode' => 'prefer',
    ],
];
