<?php
/** Sidebar navigation. Items are permission-gated. */
$nav = [
    ['/dashboard',    'Dashboard',     'icon-grid',     'dashboard.view'],
    ['/reservations', 'Reservations',  'icon-calendar', 'reservations.view'],
    ['/frontoffice',  'Front Office',  'icon-door',     'frontoffice.manage'],
    ['/rooms',        'Rooms',         'icon-bed',      'rooms.view'],
    ['/room-types',   'Room Types',    'icon-layers',   'rooms.manage'],
    ['/housekeeping', 'Housekeeping',  'icon-broom',    'housekeeping.manage'],
    ['/billing',      'Billing',       'icon-receipt',  'billing.view'],
    ['/guests',       'Guests / CRM',  'icon-users',    'guests.view'],
    ['/channels',     'Channel Mgr',   'icon-globe',    'channels.manage'],
    ['/reports',      'Reports',       'icon-chart',    'reports.view'],
    ['/hotels',       'Hotels',        'icon-building', 'hotels.manage'],
    ['/users',        'Users & Roles', 'icon-shield',   'users.manage'],
    ['/settings',     'Settings',      'icon-gear',     'settings.manage'],
    ['/logs/audit',   'Audit Logs',    'icon-list',     'logs.view'],
];
?>
<aside class="app-sidebar" id="appSidebar">
    <div class="sidebar-brand">
        <span class="brand-mark">H</span>
        <span class="brand-text"><?= e(config('app.name', 'HotelCRM')) ?></span>
    </div>
    <nav class="sidebar-nav">
        <?php foreach ($nav as [$path, $label, $icon, $perm]): ?>
            <?php if (can($perm)): ?>
                <a href="<?= url($path) ?>" class="nav-link <?= active_when($path) ?>">
                    <span class="<?= $icon ?>"></span>
                    <span class="nav-text"><?= e($label) ?></span>
                </a>
            <?php endif; ?>
        <?php endforeach; ?>
    </nav>
</aside>
