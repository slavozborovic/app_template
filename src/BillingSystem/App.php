<?php
namespace App\BillingSystem;

use DreamCommerce\ShopAppstoreLib\Client;
use DreamCommerce\ShopAppstoreLib\Handler;

class App
{
    private array $config;
    private \PDO  $db;

    public function __construct(array $config)
    {
        $this->config = $config;
        $this->db = new \PDO(
            $config['db']['connection'],
            $config['db']['user'],
            $config['db']['pass'],
            [
                \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            ]
        );
    }

    /**
     * Dispatch incoming billing webhook from App Store.
     *
     * IMPORTANT: All handler callbacks MUST return true.
     * The Handler's fire() method breaks on falsy return values.
     */
    public function dispatch(array $payload): void
    {
        $shopUrl = $payload['shop_url'] ?? '';

        $handler = new Handler(
            $shopUrl,
            $this->config['appId'],
            $this->config['appSecret'],
            $this->config['appstoreSecret']
        );

        // ── INSTALL ──
        $handler->subscribe(Handler::EVENT_INSTALL, function ($params) {
            $shopUrl  = $params['shop_url'];
            $shop     = $params['shop'];
            $authCode = $params['auth_code'];
            /** @var \DreamCommerce\ShopAppstoreLib\Client\OAuth $client */
            $client = $params['client'];

            // Exchange auth code for access + refresh tokens
            // NOTE: Use authenticate(), NOT getToken()
            $client->setAuthCode($authCode);
            $tokens = $client->authenticate(true);

            // Upsert shop record
            $existing = $this->getShopByName($shop);

            if ($existing) {
                $stmt = $this->db->prepare(
                    'UPDATE shops SET
                        shop_url  = :shop_url,
                        auth_code = :auth_code,
                        installed = 1
                     WHERE id = :id'
                );
                $stmt->execute([
                    ':shop_url'  => $shopUrl,
                    ':auth_code' => $authCode,
                    ':id'        => $existing['id'],
                ]);
                $shopId = $existing['id'];
            } else {
                $stmt = $this->db->prepare(
                    'INSERT INTO shops (shop, shop_url, auth_code, installed)
                     VALUES (:shop, :shop_url, :auth_code, 1)'
                );
                $stmt->execute([
                    ':shop'      => $shop,
                    ':shop_url'  => $shopUrl,
                    ':auth_code' => $authCode,
                ]);
                $shopId = $this->db->lastInsertId();
            }

            // Delete old tokens for this shop
            $stmt = $this->db->prepare('DELETE FROM access_tokens WHERE shop_id = :shop_id');
            $stmt->execute([':shop_id' => $shopId]);

            // Store new tokens
            $stmt = $this->db->prepare(
                'INSERT INTO access_tokens (shop_id, access_token, refresh_token, expires_at)
                 VALUES (:shop_id, :access_token, :refresh_token, :expires_at)'
            );
            $stmt->execute([
                ':shop_id'       => $shopId,
                ':access_token'  => $tokens['access_token'],
                ':refresh_token' => $tokens['refresh_token'],
                ':expires_at'    => date('Y-m-d H:i:s', time() + ($tokens['expires_in'] ?? 86400)),
            ]);

            $this->log("INSTALL: shop={$shop}, shopId={$shopId}");

            return true;
        });

        // ── UNINSTALL ──
        $handler->subscribe(Handler::EVENT_UNINSTALL, function ($params) {
            $shopUrl = $params['shop_url'] ?? '';
            $stmt = $this->db->prepare(
                'UPDATE shops SET installed = 0 WHERE shop_url = :shop_url'
            );
            $stmt->execute([':shop_url' => $shopUrl]);

            $this->log("UNINSTALL: shop_url={$shopUrl}");

            return true;
        });

        // ── BILLING INSTALL (purchase) ──
        $handler->subscribe(Handler::EVENT_BILLING_INSTALL, function ($params) {
            $shop = $this->getShopByName($params['shop'] ?? '');
            if ($shop) {
                $stmt = $this->db->prepare(
                    'INSERT INTO billings (shop_id) VALUES (:shop_id)'
                );
                $stmt->execute([':shop_id' => $shop['id']]);
            }
            $this->log("BILLING_INSTALL: " . json_encode($params->getArrayCopy()));

            return true;
        });

        // ── BILLING SUBSCRIPTION ──
        $handler->subscribe(Handler::EVENT_BILLING_SUBSCRIPTION, function ($params) {
            $shop = $this->getShopByName($params['shop'] ?? '');
            if ($shop) {
                $expiresAt = date('Y-m-d H:i:s', $params['expires_at'] ?? time());
                $stmt = $this->db->prepare(
                    'INSERT INTO subscriptions (shop_id, expires_at)
                     VALUES (:shop_id, :expires_at)'
                );
                $stmt->execute([
                    ':shop_id'    => $shop['id'],
                    ':expires_at' => $expiresAt,
                ]);
            }
            $this->log("BILLING_SUBSCRIPTION: " . json_encode($params->getArrayCopy()));

            return true;
        });

        // ── UPGRADE ──
        // Fires when app version changes. Creates shop record if missing
        // and attempts token exchange if auth_code is provided.
        $handler->subscribe(Handler::EVENT_UPGRADE, function ($params) {
            $shop     = $params['shop'] ?? '';
            $shopUrl  = $params['shop_url'] ?? '';
            $version  = $params['application_version'] ?? 1;

            $this->log("UPGRADE: " . json_encode($params->getArrayCopy()));

            // Upsert shop record
            $existing = $this->getShopByName($shop);

            if ($existing) {
                $stmt = $this->db->prepare(
                    'UPDATE shops SET shop_url = :shop_url, version = :version, installed = 1
                     WHERE id = :id'
                );
                $stmt->execute([
                    ':shop_url' => $shopUrl,
                    ':version'  => (int) $version,
                    ':id'       => $existing['id'],
                ]);
                $shopId = $existing['id'];
            } else {
                $stmt = $this->db->prepare(
                    'INSERT INTO shops (shop, shop_url, version, installed)
                     VALUES (:shop, :shop_url, :version, 1)'
                );
                $stmt->execute([
                    ':shop'     => $shop,
                    ':shop_url' => $shopUrl,
                    ':version'  => (int) $version,
                ]);
                $shopId = $this->db->lastInsertId();
            }

            // If auth_code is present, exchange for tokens (some upgrades include it)
            $authCode = $params['auth_code'] ?? null;
            if ($authCode && !empty($params['client'])) {
                try {
                    /** @var \DreamCommerce\ShopAppstoreLib\Client\OAuth $client */
                    $client = $params['client'];
                    $client->setAuthCode($authCode);
                    $tokens = $client->authenticate(true);

                    // Delete old tokens
                    $stmt = $this->db->prepare('DELETE FROM access_tokens WHERE shop_id = :shop_id');
                    $stmt->execute([':shop_id' => $shopId]);

                    // Store new tokens
                    $stmt = $this->db->prepare(
                        'INSERT INTO access_tokens (shop_id, access_token, refresh_token, expires_at)
                         VALUES (:shop_id, :access_token, :refresh_token, :expires_at)'
                    );
                    $stmt->execute([
                        ':shop_id'       => $shopId,
                        ':access_token'  => $tokens['access_token'],
                        ':refresh_token' => $tokens['refresh_token'],
                        ':expires_at'    => date('Y-m-d H:i:s', time() + ($tokens['expires_in'] ?? 86400)),
                    ]);

                    $this->log("UPGRADE: tokens obtained for shop={$shop}");
                } catch (\Exception $e) {
                    $this->log("UPGRADE: token exchange failed: " . $e->getMessage());
                }
            }

            $this->log("UPGRADE: shop={$shop}, shopId={$shopId}, version={$version}");

            return true;
        });

        // Verify HMAC hash & dispatch
        $handler->dispatch($payload);
    }

    private function getShopByName(string $shop): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM shops WHERE shop = :shop LIMIT 1');
        $stmt->execute([':shop' => $shop]);
        return $stmt->fetch() ?: null;
    }

    private function log(string $message): void
    {
        $logFile = $this->config['logFile'] ?? null;
        if ($logFile) {
            $line = '[' . date('Y-m-d H:i:s') . '] [Billing] ' . $message . "\n";
            @file_put_contents($logFile, $line, FILE_APPEND);
        }
    }
}
