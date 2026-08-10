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
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            padding: 2rem;
            background: #f0f2f5;
            color: #333;
        }
        .card {
            background: #fff;
            border-radius: 10px;
            padding: 2rem;
            max-width: 600px;
            box-shadow: 0 2px 12px rgba(0,0,0,.08);
        }
        .card h1 {
            color: #16a34a;
            font-size: 1.4rem;
            margin-bottom: 1rem;
        }
        .badge {
            display: inline-block;
            font-size: .75rem;
            font-weight: 600;
            padding: .2rem .55rem;
            border-radius: 999px;
            margin-bottom: .85rem;
            vertical-align: middle;
        }
        .badge-local {
            background: #ffedd5;
            color: #9a3412;
        }
        .badge-appstore {
            background: #dcfce7;
            color: #166534;
        }
        .info { color: #555; line-height: 1.8; }
        .info strong { color: #333; }
        .muted { margin-top: 1rem; color: #888; font-size: .85rem; }
    </style>
</head>
<body>

<div class="card">
    <?php if (!empty($isLocalMode)): ?>
        <span class="badge badge-local">tryb lokalny</span>
    <?php else: ?>
        <span class="badge badge-appstore">tryb App Store</span>
    <?php endif; ?>

    <h1>&#10004; Aplikacja dziala!</h1>
    <p class="info">
        Sklep: <strong><?= htmlspecialchars($shopData['shop'] ?? 'N/A') ?></strong><br>
        URL: <strong><?= htmlspecialchars($shopData['shop_url'] ?? 'N/A') ?></strong><br>
        Shop ID: <strong><?= htmlspecialchars((string)($shopData['id'] ?? 'N/A')) ?></strong>
    </p>
    <p class="muted">
        <?php if (!empty($isLocalMode)): ?>
            Podgląd bez osadzania w panelu Shoper (WebAPI Basic Auth).
            SDK iframe jest wyłączone. Aby wrócić do trybu App Store, otwórz aplikację z panelu sklepu
            albo wyłącz <code>local.enabled</code>.
        <?php else: ?>
            Jesli widzisz ten tekst, polaczenie z App Store dziala poprawnie.
            Teraz mozesz zaczac budowac swoja aplikacje!
        <?php endif; ?>
    </p>

    <?php if (!empty($error)): ?>
        <p style="color: #dc2626; margin-top: 1rem;">
            <?= htmlspecialchars($error) ?>
        </p>
    <?php endif; ?>

    <!-- ── Your app HTML goes here ── -->

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
