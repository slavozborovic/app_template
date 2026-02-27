<?php
/**
 * Shoper App Store — Entry Point
 *
 * Handles two types of requests:
 *   POST → Billing System webhooks (install, uninstall, billing, upgrade)
 *   GET  → App UI rendered inside Shoper admin iframe
 */

// Allow iframe embedding from any Shoper domain
header('Content-Type: text/html; charset=UTF-8');
header_remove('X-Frame-Options');
header('Content-Security-Policy: frame-ancestors https://*.shoparena.pl https://*.shoper.pl https://*.dreamcommerce.com *');
header('X-Frame-Options: ALLOWALL');

// Helper: always-available logger (works before bootstrap)
function _appLog(string $msg): void
{
    $logFile = __DIR__ . '/logs/application.log';
    @file_put_contents(
        $logFile,
        '[' . date('Y-m-d H:i:s') . '] ' . $msg . "\n",
        FILE_APPEND
    );
}

_appLog('REQUEST: ' . $_SERVER['REQUEST_METHOD'] . ' ' . ($_SERVER['REQUEST_URI'] ?? ''));

try {
    $config = require __DIR__ . '/src/bootstrap.php';

    // ── POST → Billing System webhook ──
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $payload = $_POST;

        _appLog('POST received: action=' . ($payload['action'] ?? 'EMPTY')
            . ', shop=' . ($payload['shop'] ?? 'EMPTY')
            . ', keys=' . implode(',', array_keys($payload))
        );

        // Fallback: read raw body if $_POST is empty
        if (empty($payload)) {
            $rawBody = file_get_contents('php://input');
            _appLog('POST body empty, raw input: ' . substr($rawBody, 0, 500));
            $decoded = json_decode($rawBody, true);
            if (is_array($decoded) && !empty($decoded)) {
                $payload = $decoded;
            }
            if (empty($payload) && !empty($rawBody)) {
                parse_str($rawBody, $parsed);
                if (!empty($parsed['action'])) {
                    $payload = $parsed;
                }
            }
        }

        if (empty($payload['action'])) {
            _appLog('FATAL: Missing action in POST payload');
            http_response_code(400);
            echo json_encode(['error' => 'Missing action']);
            exit;
        }

        _appLog('Dispatching billing event: ' . $payload['action']);
        $billing = new \App\BillingSystem\App($config);
        $billing->dispatch($payload);
        _appLog('Billing event dispatched OK: ' . $payload['action']);

        http_response_code(200);
        echo json_encode(['ok' => true]);
        exit;
    }

    // ── GET → App UI ──
    _appLog('GET: creating App');
    $app = new \App\App($config);

    _appLog('GET: bootstrapping (shop=' . ($_GET['shop'] ?? 'EMPTY') . ')');
    $app->bootstrap();

    _appLog('GET: dispatching controller');
    $app->dispatch();

    _appLog('GET: done');

} catch (\Throwable $e) {
    _appLog('FATAL: ' . get_class($e) . ': ' . $e->getMessage()
        . ' in ' . $e->getFile() . ':' . $e->getLine());

    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
        exit;
    }

    // Render error page
    include __DIR__ . '/view/exception.php';
}
