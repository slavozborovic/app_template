<?php
namespace App;

use DreamCommerce\ShopAppstoreLib\Client;

class App
{
    public const MODE_APPSTORE = 'appstore';
    public const MODE_LOCAL    = 'local';

    private array  $config;
    private ?\PDO  $db         = null;
    private ?array $shopData   = null;
    private ?array $tokenData  = null;
    private        $client     = null;
    private string $locale     = 'pl_PL';
    private string $mode       = self::MODE_APPSTORE;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * Bootstrap the app in App Store (OAuth + DB) or local preview (Basic Auth) mode.
     */
    public function bootstrap(): void
    {
        $this->locale = $_GET['locale'] ?? 'pl_PL';
        $this->mode   = $this->resolveMode();

        if ($this->mode === self::MODE_LOCAL) {
            $this->bootstrapLocal();
            return;
        }

        $this->bootstrapAppStore();
    }

    public function isLocalMode(): bool
    {
        return $this->mode === self::MODE_LOCAL;
    }

    public function getMode(): string
    {
        return $this->mode;
    }

    /**
     * App Store iframe: requires ?shop=... and installed shop + OAuth tokens in DB.
     */
    private function bootstrapAppStore(): void
    {
        $this->connectDb();
        $this->loadShopData();

        if (!$this->shopData || !$this->shopData['installed']) {
            throw new \RuntimeException(
                'Aplikacja nie jest zainstalowana dla tego sklepu. '
                . 'Odinstaluj i zainstaluj ponownie aplikacje w panelu Shoper.'
            );
        }

        $this->loadAndRefreshTokens();
    }

    /**
     * Local preview: no Shoper iframe / hash / install. Uses WebAPI Basic Auth.
     */
    private function bootstrapLocal(): void
    {
        $local = $this->config['local'] ?? [];

        if (empty($local['enabled'])) {
            throw new \RuntimeException(
                'Tryb lokalny jest wyłączony. Ustaw local.enabled = true w Config.local.php.'
            );
        }

        $this->assertLocalHostAllowed($local['allow_hosts'] ?? []);

        $shopUrl  = rtrim((string)($local['shop_url'] ?? ''), '/');
        $username = (string)($local['username'] ?? '');
        $password = (string)($local['password'] ?? '');

        if ($shopUrl === '' || $username === '' || $password === '') {
            throw new \RuntimeException(
                'Tryb lokalny wymaga local.shop_url, local.username i local.password '
                . 'w Config.local.php (dane logowania do panelu / WebAPI sklepu).'
            );
        }

        $this->client = Client::factory(
            Client::ADAPTER_BASIC_AUTH,
            [
                'entrypoint' => $shopUrl,
                'username'   => $username,
                'password'   => $password,
            ]
        );

        $this->shopData = [
            'id'        => 0,
            'shop'      => 'local-preview',
            'shop_url'  => $shopUrl,
            'version'   => 1,
            'auth_code' => null,
            'installed' => 1,
            'mode'      => self::MODE_LOCAL,
        ];

        $this->log('LOCAL mode bootstrapped for ' . $shopUrl);
    }

    /**
     * Prefer App Store when Shoper opens the iframe (?shop= present).
     * Otherwise use local mode when enabled.
     */
    private function resolveMode(): string
    {
        $forced = strtolower((string)($_GET['mode'] ?? ''));
        if ($forced === self::MODE_LOCAL) {
            return self::MODE_LOCAL;
        }
        if ($forced === self::MODE_APPSTORE) {
            return self::MODE_APPSTORE;
        }

        $hasShopParam = trim((string)($_GET['shop'] ?? '')) !== '';
        if ($hasShopParam) {
            return self::MODE_APPSTORE;
        }

        if (!empty($this->config['local']['enabled'])) {
            return self::MODE_LOCAL;
        }

        return self::MODE_APPSTORE;
    }

    /**
     * @param array<int, string> $allowHosts
     */
    private function assertLocalHostAllowed(array $allowHosts): void
    {
        if ($allowHosts === []) {
            return;
        }

        $host = strtolower((string)($_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? ''));
        $host = preg_replace('/:\d+$/', '', $host) ?: '';

        $normalized = [];
        foreach ($allowHosts as $allowed) {
            $normalized[] = strtolower(preg_replace('/:\d+$/', '', (string)$allowed) ?: '');
        }

        if ($host === '' || !in_array($host, $normalized, true)) {
            throw new \RuntimeException(
                'Tryb lokalny zablokowany dla hosta „' . $host . '”. '
                . 'Dodaj go do local.allow_hosts w Config.local.php '
                . 'albo uruchom na localhost.'
            );
        }
    }

    private function connectDb(): void
    {
        if ($this->db instanceof \PDO) {
            return;
        }

        $this->db = new \PDO(
            $this->config['db']['connection'],
            $this->config['db']['user'],
            $this->config['db']['pass'],
            [
                \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            ]
        );
    }

    private function loadShopData(): void
    {
        $shop = $_GET['shop'] ?? '';

        if (!$shop) {
            throw new \RuntimeException(
                'Missing shop parameter. '
                . 'Dla podglądu bez Shopera włącz tryb lokalny w Config.local.php '
                . '(local.enabled = true) i otwórz index.php bez parametru shop.'
            );
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

        $this->client = Client::factory(
            Client::ADAPTER_OAUTH,
            [
                'entrypoint'    => $this->shopData['shop_url'],
                'client_id'     => $this->config['appId'],
                'client_secret' => $this->config['appSecret'],
            ]
        );

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

    public function getDb(): \PDO
    {
        if (!$this->db instanceof \PDO) {
            $this->connectDb();
        }
        return $this->db;
    }

    public function getLocale(): string  { return $this->locale; }
    public function getShopData(): array { return $this->shopData ?? []; }
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
