<?php
/** Main authenticated admin layout. Expects $content, $authUser, $flashes. */
use App\Core\Auth;
$hotelName = config('app.name', 'HotelCRM ERP');
?>
<!doctype html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title ?? 'Dashboard') ?> · <?= e($hotelName) ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?= asset('css/icons.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/app.css') ?>">
    <meta name="csrf-token" content="<?= e($csrfToken ?? '') ?>">
</head>
<body>
<div class="app-shell">
    <?= \App\Core\View::renderPartial('layouts/sidebar', ['currentUri' => $currentUri ?? '']) ?>

    <div class="app-main">
        <header class="app-topbar">
            <button class="btn btn-sm btn-light d-lg-none" id="sidebarToggle" aria-label="Toggle menu">
                <span class="icon-menu"></span>
            </button>
            <h1 class="topbar-title"><?= e($title ?? 'Dashboard') ?></h1>
            <div class="topbar-right">
                <div class="dropdown">
                    <button class="btn btn-sm btn-light dropdown-toggle" data-bs-toggle="dropdown">
                        <span class="icon-user"></span>
                        <?= e($authUser['name'] ?? 'User') ?>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><span class="dropdown-item-text small text-muted"><?= e(Auth::role() ?? '') ?></span></li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <form method="post" action="<?= url('/logout') ?>">
                                <?= csrf_field() ?>
                                <button class="dropdown-item" type="submit">Logout</button>
                            </form>
                        </li>
                    </ul>
                </div>
            </div>
        </header>

        <main class="app-content">
            <?php foreach (($flashes ?? []) as $type => $messages): ?>
                <?php foreach ($messages as $msg): ?>
                    <div class="alert alert-<?= $type === 'error' ? 'danger' : e($type) ?> alert-dismissible fade show">
                        <?= e($msg) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endforeach; ?>
            <?php endforeach; ?>

            <?= $content ?>
        </main>
        <footer class="app-footer">
            <span>&copy; <?= date('Y') ?> <?= e($hotelName) ?> — Hotel ERP + PMS</span>
        </footer>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= asset('js/app.js') ?>"></script>
<?= \App\Core\View::section('scripts') ?>
</body>
</html>
