<?php
/**
 * Diagnostic script for Shoper App Store application.
 *
 * Deploy temporarily to debug installation issues.
 * Access directly in browser: https://your-domain.com/your-app/tools/diag.php
 *
 * !! DELETE THIS FILE from production after debugging !!
 *
 * NOTE: .htaccess blocks access to tools/ by default.
 * To use this script, temporarily move it to the app root,
 * or comment out the "RewriteRule ^tools/" line in .htaccess.
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Shoper App Diagnostics</h2><pre>\n";

// 1. PHP version
echo "1. PHP version: " . PHP_VERSION . "\n";

// 2. Logs directory
$logsDir = __DIR__ . '/../logs';
echo "2. logs/ exists: " . (is_dir($logsDir) ? 'YES' : 'NO') . "\n";
echo "   logs/ writable: " . (is_writable($logsDir) ? 'YES' : 'NO') . "\n";

// Test write
$testFile = $logsDir . '/test.txt';
$wrote = @file_put_contents($testFile, 'test');
echo "   Write test: " . ($wrote !== false ? 'OK' : 'FAILED') . "\n";
@unlink($testFile);

// 3. vendor/autoload.php
$autoload = __DIR__ . '/../vendor/autoload.php';
echo "3. vendor/autoload.php exists: " . (file_exists($autoload) ? 'YES' : 'NO') . "\n";

if (file_exists($autoload)) {
    try {
        require_once $autoload;
        echo "   Autoload loaded: OK\n";
    } catch (\Throwable $e) {
        echo "   Autoload FAILED: " . $e->getMessage() . "\n";
    }
}

// 4. SDK classes
$classes = [
    'DreamCommerce\ShopAppstoreLib\Client',
    'DreamCommerce\ShopAppstoreLib\Client\OAuth',
    'DreamCommerce\ShopAppstoreLib\Handler',
];
echo "4. SDK classes:\n";
foreach ($classes as $cls) {
    echo "   {$cls}: " . (class_exists($cls) ? 'OK' : 'MISSING') . "\n";
}

// 5. App classes (PSR-4 autoload)
$appClasses = [
    'App\App',
    'App\BillingSystem\App',
    'App\Controller\Index',
    'App\Controller\ControllerAbstract',
];
echo "5. App classes:\n";
foreach ($appClasses as $cls) {
    echo "   {$cls}: " . (class_exists($cls) ? 'OK' : 'MISSING') . "\n";
}

// 6. Config
echo "6. Config:\n";
try {
    $config = require __DIR__ . '/../src/Config.php';
    echo "   Config loaded: OK\n";
    echo "   DB connection string: " . ($config['db']['connection'] ?? 'MISSING') . "\n";
    echo "   DB user set: " . (!empty($config['db']['user']) ? 'YES' : 'EMPTY') . "\n";
    echo "   DB pass set: " . (!empty($config['db']['pass']) ? 'YES' : 'EMPTY') . "\n";
    echo "   appId: " . substr($config['appId'] ?? '', 0, 8) . "...\n";

    // Check for placeholder values
    if (strpos($config['appId'], '__') === 0) {
        echo "   WARNING: appId still has placeholder value!\n";
    }
    if (strpos($config['db']['connection'], '__') !== false) {
        echo "   WARNING: DB connection still has placeholder values!\n";
    }
} catch (\Throwable $e) {
    echo "   Config FAILED: " . $e->getMessage() . "\n";
}

// 7. Database connection
echo "7. Database:\n";
try {
    $db = new PDO(
        $config['db']['connection'],
        $config['db']['user'],
        $config['db']['pass'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    echo "   Connection: OK\n";

    // Check tables
    $tables = ['shops', 'access_tokens', 'billings', 'subscriptions'];
    foreach ($tables as $t) {
        try {
            $count = $db->query("SELECT COUNT(*) FROM {$t}")->fetchColumn();
            echo "   Table '{$t}': OK ({$count} rows)\n";
        } catch (\Throwable $e) {
            echo "   Table '{$t}': MISSING - " . $e->getMessage() . "\n";
        }
    }
} catch (\Throwable $e) {
    echo "   Connection FAILED: " . $e->getMessage() . "\n";
}

// 8. Check bootstrap
echo "8. Bootstrap:\n";
try {
    $bootstrap = __DIR__ . '/../src/bootstrap.php';
    echo "   bootstrap.php exists: " . (file_exists($bootstrap) ? 'YES' : 'NO') . "\n";
} catch (\Throwable $e) {
    echo "   Bootstrap FAILED: " . $e->getMessage() . "\n";
}

echo "\n--- Done ---\n</pre>";
