<?php /** API token management. */ ?>
<form method="post" action="<?= url('/settings/api') ?>" class="row g-2 mb-4">
    <?= csrf_field() ?>
    <div class="col-md-8"><input name="token_name" class="form-control" placeholder="Token name (e.g. Website Booking Engine)" required></div>
    <div class="col-md-4"><button class="btn btn-primary w-100">Generate Token</button></div>
</form>
<p class="small text-muted">Use the token as a bearer header: <code>Authorization: Bearer &lt;token&gt;</code> against <code>/api/v1/*</code>. The plaintext token is shown only once at creation.</p>
<div class="table-responsive"><table class="table">
    <thead><tr><th>Name</th><th>Owner</th><th>Last used</th><th>Created</th></tr></thead>
    <tbody>
    <?php if (!$tokens): ?><tr><td colspan="4" class="text-muted py-3 text-center">No tokens yet.</td></tr><?php endif; ?>
    <?php foreach ($tokens as $t): ?>
        <tr>
            <td><?= e($t['name']) ?></td>
            <td><?= e($t['user_name']) ?></td>
            <td><?= dt($t['last_used_at'],'d M H:i') ?></td>
            <td><?= dt($t['created_at']) ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table></div>
