<div class="row g-3 mb-3">
    <?php foreach ($counts as $s => $c): ?>
        <div class="col-6 col-md-3">
            <div class="card stat-card"><div class="card-body">
                <div class="stat-value text-<?= badge($s) ?>"><?= $c ?></div>
                <div class="stat-label text-capitalize"><?= e($s) ?></div>
            </div></div>
        </div>
    <?php endforeach; ?>
</div>

<div class="card mb-3"><div class="table-responsive">
<table class="table align-middle mb-0">
    <thead><tr><th>Room</th><th>Type</th><th>Occupancy</th><th>Housekeeping</th><th>Set Status</th></tr></thead>
    <tbody>
    <?php foreach ($rooms as $r): ?>
        <tr>
            <td><strong><?= e($r['number']) ?></strong></td>
            <td><?= e($r['type_name']) ?></td>
            <td><span class="badge bg-<?= badge($r['status']) ?>"><?= e($r['status']) ?></span></td>
            <td><span class="badge bg-<?= badge($r['housekeeping']) ?>"><?= e($r['housekeeping']) ?></span></td>
            <td>
                <form method="post" action="<?= url('/housekeeping/'.$r['id'].'/status') ?>" class="d-flex gap-1">
                    <?= csrf_field() ?>
                    <select name="housekeeping" class="form-select form-select-sm" style="max-width:150px">
                        <?php foreach (['clean','dirty','inspected','maintenance'] as $s): ?>
                            <option value="<?= $s ?>" <?= $r['housekeeping']===$s?'selected':'' ?>><?= ucfirst($s) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button class="btn btn-sm btn-primary">Update</button>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div></div>

<div class="card">
    <div class="card-header">Open Tasks</div>
    <div class="table-responsive"><table class="table mb-0">
        <thead><tr><th>Room</th><th>Type</th><th>Assigned</th><th>Priority</th><th>Status</th></tr></thead>
        <tbody>
        <?php if (!$tasks): ?><tr><td colspan="5" class="text-muted py-3 text-center">No open tasks.</td></tr><?php endif; ?>
        <?php foreach ($tasks as $t): ?>
            <tr>
                <td><?= e($t['room_number']) ?></td>
                <td class="text-capitalize"><?= e($t['type']) ?></td>
                <td><?= e($t['staff_name'] ?? 'Unassigned') ?></td>
                <td><?= e($t['priority']) ?></td>
                <td><span class="badge bg-<?= badge($t['status']) ?>"><?= e($t['status']) ?></span></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table></div>
</div>
