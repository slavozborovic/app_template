<!doctype html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="referrer" content="origin">
    <title>Shoper App</title>
    <script src="https://dcsaascdn.net/js/dc-sdk-1.0.5.min.js"></script>
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
        .info { color: #555; line-height: 1.8; }
        .info strong { color: #333; }
        .muted { margin-top: 1rem; color: #888; font-size: .85rem; }
    </style>
</head>
<body>

<div class="card">
    <h1>&#10004; Aplikacja dziala!</h1>
    <p class="info">
        Sklep: <strong><?= htmlspecialchars($shopData['shop'] ?? 'N/A') ?></strong><br>
        URL: <strong><?= htmlspecialchars($shopData['shop_url'] ?? 'N/A') ?></strong><br>
        Shop ID: <strong><?= htmlspecialchars($shopData['id'] ?? 'N/A') ?></strong>
    </p>
    <p class="muted">
        Jesli widzisz ten tekst, polaczenie z App Store dziala poprawnie.
        Teraz mozesz zaczac budowac swoja aplikacje!
    </p>

    <?php if (!empty($error)): ?>
        <p style="color: #dc2626; margin-top: 1rem;">
            <?= htmlspecialchars($error) ?>
        </p>
    <?php endif; ?>

    <!-- ── Your app HTML goes here ── -->

</div>

<!--
    Available PHP variables in this view:
    $shopData   — array: id, shop, shop_url, version, auth_code, installed
    $_locale    — string: e.g. 'pl_PL'
    $_shopUrl   — string: full shop URL
    $error      — string|null: error message (if any)

    To add more variables, use $this->assign('name', $value) in the controller.
-->

<script>
/**
 * DreamCommerce ShopApp SDK initialization.
 *
 * IMPORTANT: This is required to make the iframe visible inside
 * the Shoper admin panel. The sequence must be:
 *   1. new ShopApp(callback, debug)
 *   2. app.init(hash, shopUrl)
 *   3. app.show()
 */
(function() {
    var params  = new URLSearchParams(window.location.search);
    var hash    = params.get('hash') || '';
    var shopUrl = '<?= htmlspecialchars($shopData['shop_url'] ?? '') ?>';

    if (!hash) {
        console.warn('[APP] No hash parameter in URL — SDK init skipped');
        return;
    }

    var app = new ShopApp(function() {
        app.init(hash, shopUrl);
        app.show();
    }, false); // Set to true for SDK debug logging
})();
</script>

</body>
</html>
