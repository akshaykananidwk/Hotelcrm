<?php $statuses = ['','pending','confirmed','checked_in','checked_out','cancelled','no_show']; ?>
<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div class="btn-group btn-group-sm">
        <?php foreach ($statuses as $s): ?>
            <a href="<?= url('/reservations' . ($s ? '?status=' . $s : '')) ?>"
               class="btn btn-outline-secondary <?= ($status ?? '') === $s ? 'active' : '' ?>">
                <?= $s === '' ? 'All' : ucwords(str_replace('_', ' ', $s)) ?>
            </a>
        <?php endforeach; ?>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= url('/reservations/calendar') ?>" class="btn btn-sm btn-light">📅 Calendar</a>
        <?php if (can('reservations.manage')): ?>
            <a href="<?= url('/reservations/create') ?>" class="btn btn-sm btn-primary">+ New Reservation</a>
        <?php endif; ?>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead><tr>
                <th>Code</th><th>Guest</th><th>Check-in</th><th>Check-out</th>
                <th>Source</th><th>Amount</th><th>Status</th><th></th>
            </tr></thead>
            <tbody>
            <?php if (empty($reservations)): ?>
                <tr><td colspan="8" class="text-center text-muted py-4">No reservations found.</td></tr>
            <?php endif; ?>
            <?php foreach ($reservations as $r): ?>
                <tr>
                    <td><a href="<?= url('/reservations/' . $r['id']) ?>"><?= e($r['code']) ?></a></td>
                    <td><?= e($r['guest_name']) ?><br><small class="text-muted"><?= e($r['guest_phone']) ?></small></td>
                    <td><?= dt($r['check_in']) ?></td>
                    <td><?= dt($r['check_out']) ?></td>
                    <td><span class="text-capitalize"><?= e(str_replace('_', ' ', $r['source'])) ?></span></td>
                    <td><?= money($r['total_amount']) ?></td>
                    <td><span class="badge bg-<?= badge($r['status']) ?>"><?= e(str_replace('_', ' ', $r['status'])) ?></span></td>
                    <td><a href="<?= url('/reservations/' . $r['id']) ?>" class="btn btn-sm btn-light">View</a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?= \App\Core\View::renderPartial('layouts/_pagination', ['pg' => $pg, 'baseUrl' => url('/reservations')]) ?>
