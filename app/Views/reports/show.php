<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <h5 class="mb-0"><?= e($label) ?></h5>
    <form class="d-flex gap-2 align-items-end" method="get" action="<?= url('/reports/'.$type) ?>">
        <div><label class="form-label small mb-0">From</label>
            <input type="date" name="from" class="form-control form-control-sm" value="<?= e($from) ?>"></div>
        <div><label class="form-label small mb-0">To</label>
            <input type="date" name="to" class="form-control form-control-sm" value="<?= e($to) ?>"></div>
        <button class="btn btn-sm btn-primary">Apply</button>
    </form>
</div>

<?php if (!empty($report['summary'])): ?>
<div class="row g-3 mb-3">
    <?php foreach ($report['summary'] as $k => $v): ?>
        <div class="col-6 col-md-3">
            <div class="card stat-card"><div class="card-body">
                <div class="stat-value"><?= e($v) ?></div>
                <div class="stat-label"><?= e($k) ?></div>
            </div></div>
        </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        Details
        <div class="btn-group btn-group-sm">
            <a class="btn btn-light" href="<?= url('/reports/'.$type.'/export?format=csv&from='.$from.'&to='.$to) ?>">CSV</a>
            <a class="btn btn-light" href="<?= url('/reports/'.$type.'/export?format=excel&from='.$from.'&to='.$to) ?>">Excel</a>
            <a class="btn btn-light" href="<?= url('/reports/'.$type.'/export?format=pdf&from='.$from.'&to='.$to) ?>">PDF</a>
        </div>
    </div>
    <div class="table-responsive"><table class="table mb-0">
        <?php $rows = $report['rows'] ?? []; ?>
        <?php if (!empty($rows)): ?>
            <thead><tr><?php foreach (array_keys($rows[0]) as $col): ?>
                <th><?= e(ucwords(str_replace('_',' ',$col))) ?></th><?php endforeach; ?></tr></thead>
            <tbody>
            <?php foreach ($rows as $row): ?>
                <tr><?php foreach ($row as $val): ?><td><?= e($val) ?></td><?php endforeach; ?></tr>
            <?php endforeach; ?>
            </tbody>
        <?php else: ?>
            <tbody><tr><td class="text-muted text-center py-4">No data for this period.</td></tr></tbody>
        <?php endif; ?>
    </table></div>
</div>
