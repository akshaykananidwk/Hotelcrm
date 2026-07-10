<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <form class="d-flex gap-2" method="get" action="<?= url('/guests') ?>">
        <input name="q" class="form-control form-control-sm" placeholder="Search name, phone, email" value="<?= e($q) ?>" style="min-width:240px">
        <button class="btn btn-sm btn-light">Search</button>
    </form>
    <?php if (can('guests.manage')): ?>
        <a href="<?= url('/guests/create') ?>" class="btn btn-sm btn-primary">+ Add Guest</a>
    <?php endif; ?>
</div>
<div class="card"><div class="table-responsive">
<table class="table table-hover align-middle mb-0">
    <thead><tr><th>Name</th><th>Phone</th><th>Email</th><th>City</th><th>Loyalty</th><th></th></tr></thead>
    <tbody>
    <?php if (!$guests): ?><tr><td colspan="6" class="text-center text-muted py-4">No guests found.</td></tr><?php endif; ?>
    <?php foreach ($guests as $g): ?>
        <tr>
            <td><a href="<?= url('/guests/'.$g['id']) ?>"><?= e(trim($g['first_name'].' '.$g['last_name'])) ?></a>
                <?php if ($g['is_blacklisted']): ?><span class="badge bg-danger">Blacklisted</span><?php endif; ?></td>
            <td><?= e($g['phone']) ?></td>
            <td><?= e($g['email'] ?: '—') ?></td>
            <td><?= e($g['city'] ?: '—') ?></td>
            <td><span class="badge bg-secondary text-capitalize"><?= e($g['loyalty_tier']) ?></span> <?= (int)$g['loyalty_points'] ?> pts</td>
            <td class="text-end"><a href="<?= url('/guests/'.$g['id']) ?>" class="btn btn-sm btn-light">View</a></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div></div>
<?= \App\Core\View::renderPartial('layouts/_pagination', ['pg' => $pg, 'baseUrl' => url('/guests?q='.urlencode($q))]) ?>
