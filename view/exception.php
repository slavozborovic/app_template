<!doctype html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="referrer" content="origin">
    <title>Error</title>
    <script src="https://dcsaascdn.net/js/dc-sdk-1.0.5.min.js"></script>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f0f2f5;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
        }
        .error-card {
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 4px 24px rgba(0,0,0,.08);
            padding: 2.5rem;
            max-width: 520px;
            text-align: center;
        }
        h1 { font-size: 1.3rem; color: #b91c1c; margin-bottom: 1rem; }
        p { font-size: .9rem; color: #666; line-height: 1.6; }
        .hint {
            background: #fefce8;
            border: 1px solid #fde68a;
            border-radius: 8px;
            padding: 1rem;
            margin-top: 1.2rem;
            font-size: .85rem;
            color: #92400e;
            text-align: left;
        }
        .hint strong { color: #78350f; }
        code {
            display: block;
            background: #fef2f2;
            border: 1px solid #fecaca;
            border-radius: 6px;
            padding: .8rem;
            margin-top: 1rem;
            font-size: .8rem;
            color: #991b1b;
            text-align: left;
            word-break: break-all;
        }
    </style>
</head>
<body>
<div class="error-card">
    <h1>Wystapil blad aplikacji</h1>
    <p>Cos poszlo nie tak. Sprobuj ponownie lub skontaktuj sie ze wsparciem.</p>

    <div class="hint">
        <strong>Wskazowka:</strong> Jesli widzisz ten blad po raz pierwszy,
        przejdz do panelu administracyjnego Shoper &rarr; <strong>Aplikacje</strong> &rarr;
        odinstaluj te aplikacje, a nastepnie zainstaluj ja ponownie.
        To wygeneruje nowe tokeny dostepu.
    </div>

    <?php if (isset($e) && ($config['debug'] ?? false)): ?>
        <code><?= htmlspecialchars($e->getMessage()) ?></code>
    <?php endif; ?>
</div>

<script>
// Try to initialize SDK even on error page — so iframe becomes visible
(function() {
    try {
        var params = new URLSearchParams(window.location.search);
        var hash = params.get('hash') || '';
        if (hash && typeof ShopApp !== 'undefined') {
            var app = new ShopApp(function() {
                app.init(hash, '');
                app.show();
            }, false);
        }
    } catch(e) { /* ignore SDK errors on error page */ }
})();
</script>
</body>
</html>
