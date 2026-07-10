<?php
/**
 * Application configuration (sample).
 *
 * Copy this file to `config.php` and adjust the values for your environment,
 * or run the installation wizard at /install which will generate it for you.
 *
 * NOTE: config.php is git-ignored so real credentials never reach the repo.
 */

return [
    // ---------------------------------------------------------------------
    // Application
    // ---------------------------------------------------------------------
    'app' => [
        'name'      => 'HotelCRM ERP',
        'env'       => 'production',        // production | development
        'debug'     => false,              // show detailed errors when true
        'url'       => 'http://localhost', // base URL, no trailing slash
        'timezone'  => 'Asia/Kolkata',
        'key'       => 'change-this-to-a-long-random-string', // used for signing
        'locale'    => 'en',
    ],

    // ---------------------------------------------------------------------
    // Database (MySQL / MariaDB only)
    // ---------------------------------------------------------------------
    'db' => [
        'host'    => '127.0.0.1',
        'port'    => 3306,
        'name'    => 'hotelcrm',
        'user'    => 'root',
        'pass'    => '',
        'charset' => 'utf8mb4',
    ],

    // ---------------------------------------------------------------------
    // Session
    // ---------------------------------------------------------------------
    'session' => [
        'name'     => 'HOTELCRM_SESS',
        'lifetime' => 7200,   // seconds
        'secure'   => false,  // set true behind HTTPS
        'httponly' => true,
        'samesite' => 'Lax',
    ],

    // ---------------------------------------------------------------------
    // Security
    // ---------------------------------------------------------------------
    'security' => [
        'password_algo'  => PASSWORD_DEFAULT,
        'rate_limit'     => 60,    // max requests / minute / IP for API + login
        'max_login_try'  => 5,     // lock after N failed attempts
        'lockout_time'   => 900,   // seconds
        'upload_max_kb'  => 5120,  // 5 MB
        'upload_allowed' => ['jpg', 'jpeg', 'png', 'pdf', 'webp'],
    ],

    // ---------------------------------------------------------------------
    // Paths (absolute, resolved at runtime)
    // ---------------------------------------------------------------------
    'paths' => [
        'uploads' => __DIR__ . '/../public/uploads',
        'logs'    => __DIR__ . '/../storage/logs',
        'cache'   => __DIR__ . '/../storage/cache',
        'backups' => __DIR__ . '/../storage/backups',
    ],
];
