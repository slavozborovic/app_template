<?php
require_once __DIR__ . '/../vendor/autoload.php';

$config = require __DIR__ . '/Config.php';

date_default_timezone_set($config['timezone']);

if (($config['php']['display_errors'] ?? 'off') === 'on') {
    ini_set('display_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    error_reporting(0);
}

return $config;
