<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0">Hotels</h5>
    <a href="<?= url('/hotels/create') ?>" class="btn btn-sm btn-primary">+ Add Hotel</a>
</div>
<div class="card"><div class="table-responsive">
<table class="table table-hover align-middle mb-0">
    <thead><tr><th>Name</th><th>City</th><th>GSTIN</th><th>Currency</th><th>Status</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($hotels as $h): ?>
        <tr>
            <td><strong><?= e($h['name']) ?></strong><br><small class="text-muted"><?= e($h['email']) ?></small></td>
            <td><?= e($h['city']) ?></td>
            <td><?= e($h['gst_number'] ?? '—') ?></td>
            <td><?= e($h['currency']) ?></td>
            <td><span class="badge bg-<?= badge($h['status']) ?>"><?= e($h['status']) ?></span></td>
            <td class="text-end">
                <a href="<?= url('/hotels/'.$h['id'].'/edit') ?>" class="btn btn-sm btn-light">Edit</a>
                <form method="post" action="<?= url('/hotels/'.$h['id']) ?>" class="d-inline" onsubmit="return confirm('Delete hotel?')">
                    <?= csrf_field() ?><input type="hidden" name="_method" value="DELETE">
                    <button class="btn btn-sm btn-outline-danger">Delete</button>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div></div>
