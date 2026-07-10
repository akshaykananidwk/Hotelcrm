<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0">Room Types</h5>
    <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#typeModal">+ Add Type</button>
</div>

<div class="card"><div class="table-responsive">
<table class="table align-middle mb-0">
    <thead><tr><th>Name</th><th>Code</th><th>Base Price</th><th>Max Adults</th><th>Tax %</th><th>Status</th></tr></thead>
    <tbody>
    <?php foreach ($types as $t): ?>
        <tr>
            <td><strong><?= e($t['name']) ?></strong><br><small class="text-muted"><?= e($t['description']) ?></small></td>
            <td><?= e($t['code']) ?></td>
            <td><?= money($t['base_price']) ?></td>
            <td><?= (int)$t['max_adults'] ?></td>
            <td><?= e($t['tax_rate']) ?>%</td>
            <td><span class="badge bg-<?= badge($t['status']) ?>"><?= e($t['status']) ?></span></td>
        </tr>
    <?php endforeach; ?>
    <?php if (!$types): ?><tr><td colspan="6" class="text-muted py-3 text-center">No room types yet.</td></tr><?php endif; ?>
    </tbody>
</table>
</div></div>

<div class="modal fade" id="typeModal"><div class="modal-dialog"><div class="modal-content">
    <form method="post" action="<?= url('/room-types') ?>">
        <?= csrf_field() ?>
        <div class="modal-header"><h5 class="modal-title">Add Room Type</h5>
            <button class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body row g-3">
            <div class="col-8"><label class="form-label">Name</label><input name="name" class="form-control" required></div>
            <div class="col-4"><label class="form-label">Code</label><input name="code" class="form-control"></div>
            <div class="col-12"><label class="form-label">Description</label><input name="description" class="form-control"></div>
            <div class="col-4"><label class="form-label">Base price</label><input name="base_price" type="number" step="0.01" class="form-control" required></div>
            <div class="col-4"><label class="form-label">Max adults</label><input name="max_adults" type="number" class="form-control" value="2" required></div>
            <div class="col-4"><label class="form-label">Tax %</label><input name="tax_rate" type="number" step="0.01" class="form-control" value="12"></div>
        </div>
        <div class="modal-footer"><button class="btn btn-primary">Save</button></div>
    </form>
</div></div></div>
