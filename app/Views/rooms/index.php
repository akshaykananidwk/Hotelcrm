<div class="row g-3 mb-3">
    <?php foreach ($statusCount as $s => $c): $col=['available'=>'success','occupied'=>'primary','blocked'=>'dark','maintenance'=>'warning'][$s]; ?>
        <div class="col-6 col-md-3">
            <div class="card stat-card"><div class="card-body">
                <div class="stat-value text-<?= $col ?>"><?= $c ?></div>
                <div class="stat-label text-capitalize"><?= e($s) ?></div>
            </div></div>
        </div>
    <?php endforeach; ?>
</div>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0">Rooms</h5>
    <?php if (can('rooms.manage')): ?>
        <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#roomModal">+ Add Room</button>
    <?php endif; ?>
</div>

<div class="card"><div class="table-responsive">
<table class="table table-hover align-middle mb-0">
    <thead><tr><th>Number</th><th>Type</th><th>Floor</th><th>Status</th><th>Housekeeping</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($rooms as $r): ?>
        <tr>
            <td><strong><?= e($r['number']) ?></strong></td>
            <td><?= e($r['type_name']) ?></td>
            <td><?= e($r['floor_name'] ?? '—') ?></td>
            <td><span class="badge bg-<?= badge($r['status']) ?>"><?= e($r['status']) ?></span></td>
            <td><span class="badge bg-<?= badge($r['housekeeping']) ?>"><?= e($r['housekeeping']) ?></span></td>
            <td class="text-end">
                <?php if (can('rooms.manage')): ?>
                <form method="post" action="<?= url('/rooms/'.$r['id']) ?>" class="d-inline"
                      onsubmit="return confirm('Delete room <?= e($r['number']) ?>?')">
                    <?= csrf_field() ?><input type="hidden" name="_method" value="DELETE">
                    <button class="btn btn-sm btn-outline-danger">Delete</button>
                </form>
                <?php endif; ?>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div></div>

<?php if (can('rooms.manage')): ?>
<div class="modal fade" id="roomModal"><div class="modal-dialog"><div class="modal-content">
    <form method="post" action="<?= url('/rooms') ?>">
        <?= csrf_field() ?>
        <div class="modal-header"><h5 class="modal-title">Add Room</h5>
            <button class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body row g-3">
            <div class="col-6"><label class="form-label">Room number</label>
                <input name="number" class="form-control" required></div>
            <div class="col-6"><label class="form-label">Room type</label>
                <select name="room_type_id" class="form-select" required>
                    <?php foreach ($types as $t): ?><option value="<?= $t['id'] ?>"><?= e($t['name']) ?></option><?php endforeach; ?>
                </select></div>
            <div class="col-6"><label class="form-label">Floor</label>
                <select name="floor_id" class="form-select"><option value="">—</option>
                    <?php foreach ($floors as $f): ?><option value="<?= $f['id'] ?>"><?= e($f['name']) ?></option><?php endforeach; ?>
                </select></div>
            <div class="col-6"><label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <?php foreach (['available','blocked','maintenance'] as $s): ?><option value="<?= $s ?>"><?= ucfirst($s) ?></option><?php endforeach; ?>
                </select></div>
        </div>
        <div class="modal-footer"><button class="btn btn-primary">Save Room</button></div>
    </form>
</div></div></div>
<?php endif; ?>
