<?php
$autoload = __DIR__ . '/../vendor/autoload.php';

if (!file_exists($autoload)) {
    throw new \RuntimeException(
        'Brak katalogu vendor/. Uruchom "composer install" lokalnie '
        . 'i wgraj folder vendor/ na serwer (albo uruchom composer install na hostingu).'
    );
}

require_once $autoload;

$config = require __DIR__ . '/Config.php';
$localConfig = __DIR__ . '/Config.local.php';
if (is_readable($localConfig)) {
    $config = array_replace_recursive($config, require $localConfig);
}

date_default_timezone_set($config['timezone']);

if (($config['php']['display_errors'] ?? 'off') === 'on') {
    ini_set('display_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    error_reporting(0);
}

return $config;
