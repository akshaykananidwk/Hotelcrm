<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0">Channel Manager</h5>
    <a href="<?= url('/settings/ota') ?>" class="btn btn-sm btn-light">Configure Credentials</a>
</div>

<div class="row g-3 mb-4">
    <?php foreach ($channels as $c): ?>
        <div class="col-md-4 col-lg-3">
            <div class="card h-100"><div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <h6 class="mb-1"><?= e($c['display_name']) ?></h6>
                    <span class="badge bg-<?= $c['is_enabled'] ? 'success' : 'secondary' ?>">
                        <?= $c['is_enabled'] ? 'Enabled' : 'Off' ?>
                    </span>
                </div>
                <div class="small text-muted mb-2">
                    Last sync: <?= dt($c['last_sync_at'], 'd M H:i') ?>
                    <span class="badge bg-<?= badge($c['last_status']) ?>"><?= e($c['last_status']) ?></span>
                </div>
                <?php if (can('channels.manage')): ?>
                <form method="post" action="<?= url('/channels/'.$c['id'].'/sync') ?>">
                    <?= csrf_field() ?>
                    <button class="btn btn-sm btn-primary w-100" <?= $c['is_enabled'] ? '' : 'disabled' ?>>Sync Now</button>
                </form>
                <?php endif; ?>
            </div></div>
        </div>
    <?php endforeach; ?>
</div>

<div class="card">
    <div class="card-header">Recent Sync Jobs</div>
    <div class="table-responsive"><table class="table mb-0">
        <thead><tr><th>#</th><th>Channel</th><th>Type</th><th>Status</th><th>Attempts</th><th>When</th><th>Error</th></tr></thead>
        <tbody>
        <?php if (!$recentJobs): ?><tr><td colspan="7" class="text-muted py-3 text-center">No sync jobs yet.</td></tr><?php endif; ?>
        <?php foreach ($recentJobs as $j): ?>
            <tr>
                <td><?= (int)$j['id'] ?></td>
                <td><?= e($j['channel']) ?></td>
                <td><?= e($j['job_type']) ?></td>
                <td><span class="badge bg-<?= badge($j['status']) ?>"><?= e($j['status']) ?></span></td>
                <td><?= (int)$j['attempts'] ?>/<?= (int)$j['max_attempts'] ?></td>
                <td><?= dt($j['created_at'],'d M H:i') ?></td>
                <td class="small text-danger"><?= e($j['error'] ?? '') ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table></div>
</div>
<p class="small text-muted mt-2">Sync jobs are processed asynchronously by the worker: <code>php cli/worker.php</code> (run every minute via cron).</p>
