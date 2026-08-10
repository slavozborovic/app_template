<?php
/**
 * Application Configuration
 *
 * Replace all __PLACEHOLDER__ values with your real credentials
 * before deploying. You get these from the Shoper App Store
 * developer panel after registering your application.
 * Optional local overrides (not committed): src/Config.local.php
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
     */
    'db' => [
        'connection' => 'mysql:host=__DB_HOST__;dbname=__DB_NAME__',
        'user'       => '__DB_USER__',
        'pass'       => '__DB_PASS__',
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
