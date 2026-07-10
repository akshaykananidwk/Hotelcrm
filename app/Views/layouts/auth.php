<?php /** Minimal centered layout for login / guest pages. */ ?>
<!doctype html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title ?? 'Login') ?> · <?= e(config('app.name', 'HotelCRM')) ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?= asset('css/app.css') ?>">
</head>
<body class="auth-body">
    <div class="auth-card-wrap">
        <?php foreach (($flashes ?? []) as $type => $messages): ?>
            <?php foreach ($messages as $msg): ?>
                <div class="alert alert-<?= $type === 'error' ? 'danger' : e($type) ?>"><?= e($msg) ?></div>
            <?php endforeach; ?>
        <?php endforeach; ?>
        <?= $content ?>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
