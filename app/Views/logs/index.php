<div class="d-flex gap-2 mb-3">
    <a href="<?= url('/logs/audit') ?>" class="btn btn-sm btn-<?= $type==='audit'?'primary':'light' ?>">Audit Trail</a>
    <a href="<?= url('/logs/activity') ?>" class="btn btn-sm btn-<?= $type==='login'?'primary':'light' ?>">Login Activity</a>
</div>
<div class="card"><div class="table-responsive">
<?php if ($type === 'audit'): ?>
    <table class="table mb-0"><thead><tr><th>When</th><th>User</th><th>Action</th><th>Entity</th><th>IP</th></tr></thead>
    <tbody>
    <?php foreach ($logs as $l): ?>
        <tr>
            <td class="small"><?= dt($l['created_at'],'d M H:i:s') ?></td>
            <td><?= e($l['user_name'] ?? 'System') ?></td>
            <td><code><?= e($l['action']) ?></code></td>
            <td><?= e($l['entity']) ?> <?= $l['entity_id'] ? '#'.e($l['entity_id']) : '' ?></td>
            <td class="small text-muted"><?= e($l['ip_address']) ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody></table>
<?php else: ?>
    <table class="table mb-0"><thead><tr><th>When</th><th>Email</th><th>Result</th><th>IP</th></tr></thead>
    <tbody>
    <?php foreach ($logs as $l): ?>
        <tr>
            <td class="small"><?= dt($l['created_at'],'d M H:i:s') ?></td>
            <td><?= e($l['email']) ?></td>
            <td><span class="badge bg-<?= $l['success']?'success':'danger' ?>"><?= $l['success']?'Success':'Failed' ?></span></td>
            <td class="small text-muted"><?= e($l['ip_address']) ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody></table>
<?php endif; ?>
</div></div>
<?= \App\Core\View::renderPartial('layouts/_pagination', ['pg' => $pg, 'baseUrl' => url('/logs/'.($type==='audit'?'audit':'activity'))]) ?>
