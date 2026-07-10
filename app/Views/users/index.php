<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0">Users & Roles</h5>
    <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#userModal">+ Add User</button>
</div>
<div class="card"><div class="table-responsive">
<table class="table table-hover align-middle mb-0">
    <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Hotel</th><th>Status</th><th>Last login</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($users as $u): ?>
        <tr>
            <td><strong><?= e($u['name']) ?></strong></td>
            <td><?= e($u['email']) ?></td>
            <td><span class="badge bg-info"><?= e($u['role_name']) ?></span></td>
            <td><?= e($u['hotel_name'] ?? 'Platform') ?></td>
            <td><span class="badge bg-<?= badge($u['status']) ?>"><?= e($u['status']) ?></span></td>
            <td class="small text-muted"><?= dt($u['last_login_at'],'d M H:i') ?></td>
            <td class="text-end">
                <form method="post" action="<?= url('/users/'.$u['id']) ?>" class="d-inline" onsubmit="return confirm('Delete user?')">
                    <?= csrf_field() ?><input type="hidden" name="_method" value="DELETE">
                    <button class="btn btn-sm btn-outline-danger">Delete</button>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div></div>

<div class="modal fade" id="userModal"><div class="modal-dialog"><div class="modal-content">
    <form method="post" action="<?= url('/users') ?>">
        <?= csrf_field() ?>
        <div class="modal-header"><h5 class="modal-title">Add User</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body row g-3">
            <div class="col-6"><label class="form-label">Name</label><input name="name" class="form-control" required></div>
            <div class="col-6"><label class="form-label">Email</label><input name="email" type="email" class="form-control" required></div>
            <div class="col-6"><label class="form-label">Password</label><input name="password" type="password" class="form-control" required></div>
            <div class="col-6"><label class="form-label">Role</label>
                <select name="role_id" class="form-select" required>
                    <?php foreach ($roles as $r): ?><option value="<?= $r['id'] ?>"><?= e($r['name']) ?></option><?php endforeach; ?>
                </select></div>
        </div>
        <div class="modal-footer"><button class="btn btn-primary">Create User</button></div>
    </form>
</div></div></div>
