<?php
/**
 * Minimal Composer-compatible PSR-4 autoloader.
 * Generated for deployment without local PHP/Composer.
 */

spl_autoload_register(static function ($class) {
    static $prefixes = [
        'App\\' => __DIR__ . '/../src/',
        'DreamCommerce\\' => __DIR__ . '/dreamcommerce/shop-appstore-lib/src/DreamCommerce/',
        'Psr\\Log\\' => __DIR__ . '/psr/log/Psr/Log/',
    ];

    foreach ($prefixes as $prefix => $baseDir) {
        $len = strlen($prefix);
        if (strncmp($prefix, $class, $len) !== 0) {
            continue;
        }

        $relative = substr($class, $len);
        $file = $baseDir . str_replace('\\', '/', $relative) . '.php';

        if (is_file($file)) {
            require $file;
            return;
        }
    }
});
