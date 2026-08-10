<?php
/**
 * Application Configuration
 *
 * Replace all __PLACEHOLDER__ values with your real credentials
 * before deploying. You get these from the Shoper App Store
 * developer panel after registering your application.
 *
 * Optional local overrides (not committed): src/Config.local.php
 * See Config.local.php.example for dual-mode (App Store + local preview).
 */
return [
    /*
     * Application ID — from Shoper App Store developer panel
     */
    'appId' => '__APP_ID__',

    /*
     * App Secret — from Shoper App Store developer panel
     */
    'appSecret' => '__APP_SECRET__',

    /*
     * AppStore Secret — from Shoper App Store developer panel
     */
    'appstoreSecret' => '__APPSTORE_SECRET__',

    /*
     * Database configuration (MySQL)
     *
     * Import sql/mysql.sql into your database first:
     *   mysql -u USER -p DB_NAME < sql/mysql.sql
     *
     * Not required for local preview mode (Basic Auth).
     */
    'db' => [
        'connection' => 'mysql:host=__DB_HOST__;dbname=__DB_NAME__;charset=utf8mb4',
        'user'       => '__DB_USER__',
        'pass'       => '__DB_PASS__',
    ],

    /*
     * Local preview mode — open index.php in a browser without Shoper iframe.
     * Enable ONLY in Config.local.php (never on public App Store production).
     *
     * Uses shop WebAPI Basic Auth (panel admin login/password).
     */
    'local' => [
        'enabled'     => false,
        'shop_url'    => 'https://twoj-sklep.shoparena.pl',
        'username'    => '',
        'password'    => '',
        // Optional host allowlist. Empty = allow any host (use only on trusted machines).
        'allow_hosts' => ['localhost', '127.0.0.1', '::1'],
    ],

    /*
     * App settings
     */
    'debug'    => true,       // Set to false in production
    'logFile'  => __DIR__ . '/../logs/application.log',
    'timezone' => 'Europe/Warsaw',

    'php' => [
        'display_errors' => 'off',  // Set to 'on' only for local development
    ],
];
