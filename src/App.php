<?php
namespace App;

use DreamCommerce\ShopAppstoreLib\Client;
use DreamCommerce\ShopAppstoreLib\Client\OAuth;

class App
{
    private array  $config;
    private \PDO   $db;
    private ?array $shopData   = null;
    private ?array $tokenData  = null;
    private        $client     = null;
    private string $locale     = 'pl_PL';

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
     * Bootstrap the app: load shop, verify tokens, build OAuth client.
     */
    public function bootstrap(): void
    {
        $this->locale = $_GET['locale'] ?? 'pl_PL';

        // 1. Load shop from DB
        $this->loadShopData();

        if (!$this->shopData || !$this->shopData['installed']) {
            throw new \RuntimeException(
                'Aplikacja nie jest zainstalowana dla tego sklepu. '
                . 'Odinstaluj i zainstaluj ponownie aplikacje w panelu Shoper.'
            );
        }

        // 2. Load tokens + refresh if near expiry
        $this->loadAndRefreshTokens();
    }

    private function loadShopData(): void
    {
        $shop = $_GET['shop'] ?? '';

        if (!$shop) {
            throw new \RuntimeException('Missing shop parameter.');
        }

        $stmt = $this->db->prepare(
            'SELECT * FROM shops WHERE shop = :shop AND installed = 1 LIMIT 1'
        );
        $stmt->execute([':shop' => $shop]);
        $this->shopData = $stmt->fetch() ?: null;
    }

    private function loadAndRefreshTokens(): void
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM access_tokens
             WHERE shop_id = :shop_id
             ORDER BY created_at DESC
             LIMIT 1'
        );
        $stmt->execute([':shop_id' => $this->shopData['id']]);
        $this->tokenData = $stmt->fetch();

        if (!$this->tokenData) {
            throw new \RuntimeException(
                'Brak tokenow dostepu dla tego sklepu. '
                . 'Odinstaluj i zainstaluj ponownie aplikacje w panelu Shoper, '
                . 'aby wygenerowac nowe tokeny.'
            );
        }

        // Create OAuth client
        $this->client = Client::factory(
            Client::ADAPTER_OAUTH,
            [
                'entrypoint'    => $this->shopData['shop_url'],
                'client_id'     => $this->config['appId'],
                'client_secret' => $this->config['appSecret'],
            ]
        );

        // Refresh token if expires within 24 hours
        $expiresAt = strtotime($this->tokenData['expires_at']);
        if ($expiresAt && ($expiresAt - time()) < 86400) {
            try {
                $this->client->setRefreshToken($this->tokenData['refresh_token']);
                $newTokens = $this->client->refreshTokens();

                $stmt = $this->db->prepare(
                    'UPDATE access_tokens SET
                        access_token  = :access_token,
                        refresh_token = :refresh_token,
                        expires_at    = :expires_at
                     WHERE id = :id'
                );
                $stmt->execute([
                    ':access_token'  => $newTokens['access_token'],
                    ':refresh_token' => $newTokens['refresh_token'],
                    ':expires_at'    => date('Y-m-d H:i:s', time() + $newTokens['expires_in']),
                    ':id'            => $this->tokenData['id'],
                ]);

                $this->tokenData['access_token'] = $newTokens['access_token'];
            } catch (\Exception $e) {
                $this->log('Token refresh failed: ' . $e->getMessage());
            }
        }

        $this->client->setAccessToken($this->tokenData['access_token']);
    }

    /**
     * Route request to controller/action.
     * URL format: ?q=controller/action
     */
    public function dispatch(): void
    {
        $q = $_GET['q'] ?? 'index/index';
        $parts = explode('/', $q, 2);
        $controllerName = ucfirst($parts[0] ?? 'index');
        $actionName     = ($parts[1] ?? 'index') . 'Action';

        $className = '\\App\\Controller\\' . $controllerName;
        if (!class_exists($className)) {
            throw new \RuntimeException("Controller not found: {$controllerName}");
        }

        $controller = new $className($this);

        if (!method_exists($controller, $actionName)) {
            throw new \RuntimeException("Action not found: {$actionName}");
        }

        $controller->$actionName();
    }

    // ── Accessors ──

    public function getClient()          { return $this->client; }
    public function getDb(): \PDO        { return $this->db; }
    public function getLocale(): string  { return $this->locale; }
    public function getShopData(): array { return $this->shopData; }
    public function getConfig(): array   { return $this->config; }

    public static function escapeHtml(string $str): string
    {
        return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
    }

    public function log(string $message): void
    {
        $logFile = $this->config['logFile'] ?? null;
        if ($logFile) {
            $line = '[' . date('Y-m-d H:i:s') . '] ' . $message . "\n";
            @file_put_contents($logFile, $line, FILE_APPEND);
        }
    }
}
