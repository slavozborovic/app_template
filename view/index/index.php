<!doctype html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="referrer" content="origin">
    <title>Shoper App<?= !empty($isLocalMode) ? ' (local)' : '' ?></title>
    <?php if (empty($isLocalMode)): ?>
    <script src="https://dcsaascdn.net/js/dc-sdk-1.0.5.min.js"></script>
    <?php endif; ?>
    <style>
        :root {
            --bg: #f3f4f6;
            --panel: #ffffff;
            --ink: #1f2937;
            --muted: #6b7280;
            --label: #374151;
            --line: #e5e7eb;
            --line-strong: #d1d5db;
            --blue: #1574f5;
            --blue-hover: #0f63d6;
            --blue-soft: #eff6ff;
            --ok: #15803d;
            --ok-bg: #ecfdf5;
            --warn-bg: #fff7ed;
            --warn: #9a3412;
            --radius: 8px;
            --font: Inter, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            --shadow: 0 1px 2px rgba(16, 24, 40, .04);
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: var(--font);
            background: var(--bg);
            color: var(--ink);
            min-height: 100vh;
            font-size: 14px;
            line-height: 1.45;
            padding: 20px;
        }
        .wrap {
            max-width: 720px;
            margin: 0 auto;
        }
        .page-header {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 16px;
            padding-bottom: 16px;
            border-bottom: 1px solid var(--line);
        }
        .page-header h1 {
            font-size: 24px;
            font-weight: 700;
            letter-spacing: -.02em;
            color: #111827;
        }
        .badge-mode {
            display: inline-flex;
            align-items: center;
            height: 24px;
            padding: 0 10px;
            border-radius: 999px;
            border: 1px solid var(--line);
            background: #fff;
            color: var(--muted);
            font-size: 12px;
            font-weight: 600;
        }
        .badge-mode.local {
            border-color: #fdba74;
            background: var(--warn-bg);
            color: var(--warn);
        }
        .badge-mode.appstore {
            border-color: #bbf7d0;
            background: var(--ok-bg);
            color: var(--ok);
        }
        .panel {
            background: var(--panel);
            border: 1px solid var(--line);
            border-radius: 10px;
            box-shadow: var(--shadow);
            padding: 20px;
        }
        .panel-title {
            margin: 0 0 14px;
            padding-bottom: 10px;
            border-bottom: 1px solid var(--line);
            font-size: 15px;
            font-weight: 700;
            color: #111827;
        }
        .status-ok {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 16px;
            font-size: 16px;
            font-weight: 700;
            color: #111827;
        }
        .status-ok .check {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            background: var(--ok-bg);
            color: var(--ok);
            display: grid;
            place-items: center;
            font-size: 14px;
            flex: 0 0 auto;
        }
        .info-grid {
            display: grid;
            gap: 10px;
        }
        .info-row {
            display: grid;
            gap: 4px;
        }
        .info-row label {
            font-size: 13px;
            font-weight: 600;
            color: var(--label);
        }
        .info-row .value {
            min-height: 40px;
            border: 1px solid var(--line-strong);
            border-radius: 6px;
            padding: 10px 12px;
            background: #fff;
            color: var(--ink);
            word-break: break-all;
        }
        .info-callout {
            display: flex;
            gap: 10px;
            align-items: flex-start;
            margin-top: 16px;
            padding: 12px 14px;
            background: var(--blue-soft);
            border-left: 4px solid var(--blue);
            border-radius: 0 6px 6px 0;
            color: #1e3a5f;
            font-size: 13px;
            line-height: 1.45;
        }
        .info-callout .info-ico {
            flex: 0 0 auto;
            width: 18px;
            height: 18px;
            border-radius: 50%;
            background: var(--blue);
            color: #fff;
            font-size: 12px;
            font-weight: 700;
            display: grid;
            place-items: center;
            margin-top: 1px;
        }
        .error-banner {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #991b1b;
            border-radius: 8px;
            padding: 12px 14px;
            margin-top: 16px;
        }
        code {
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            font-size: 12px;
            background: #f3f4f6;
            border: 1px solid var(--line);
            border-radius: 4px;
            padding: 1px 5px;
        }
    </style>
</head>
<body>

<div class="wrap">
    <header class="page-header">
        <h1>Shoper App</h1>
        <?php if (!empty($isLocalMode)): ?>
            <span class="badge-mode local">tryb lokalny</span>
        <?php else: ?>
            <span class="badge-mode appstore">App Store</span>
        <?php endif; ?>
    </header>

    <div class="panel">
        <h2 class="panel-title">Status aplikacji</h2>

        <div class="status-ok">
            <span class="check" aria-hidden="true">✓</span>
            <span>Aplikacja działa</span>
        </div>

        <div class="info-grid">
            <div class="info-row">
                <label>Sklep</label>
                <div class="value"><?= htmlspecialchars($shopData['shop'] ?? 'N/A') ?></div>
            </div>
            <div class="info-row">
                <label>URL</label>
                <div class="value"><?= htmlspecialchars($shopData['shop_url'] ?? 'N/A') ?></div>
            </div>
            <div class="info-row">
                <label>Shop ID</label>
                <div class="value"><?= htmlspecialchars((string)($shopData['id'] ?? 'N/A')) ?></div>
            </div>
        </div>

        <div class="info-callout">
            <span class="info-ico" aria-hidden="true">i</span>
            <div>
                <?php if (!empty($isLocalMode)): ?>
                    Podgląd bez osadzania w panelu Shoper (WebAPI Basic Auth).
                    SDK iframe jest wyłączone. Aby wrócić do trybu App Store, otwórz aplikację z panelu sklepu
                    albo wyłącz <code>local.enabled</code>.
                <?php else: ?>
                    Połączenie z App Store działa poprawnie. Możesz zacząć budować swoją aplikację.
                <?php endif; ?>
            </div>
        </div>

        <?php if (!empty($error)): ?>
            <div class="error-banner"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <!-- ── Your app HTML goes here ── -->
    </div>
</div>

<script>
(function() {
    var isLocal = <?= !empty($isLocalMode) ? 'true' : 'false' ?>;
    if (isLocal || typeof ShopApp === 'undefined') {
        return;
    }

    var params  = new URLSearchParams(window.location.search);
    var hash    = params.get('hash') || '';
    var shopUrl = <?= json_encode($shopData['shop_url'] ?? '', JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;

    if (!hash) {
        console.warn('[APP] No hash parameter in URL — SDK init skipped');
        return;
    }

    var app = new ShopApp(function() {
        app.init(hash, shopUrl);
        app.show();
    }, false);
})();
</script>

</body>
</html>
