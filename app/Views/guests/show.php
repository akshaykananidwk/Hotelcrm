<div class="row g-3">
    <div class="col-lg-4">
        <div class="card mb-3">
            <div class="card-body text-center">
                <div class="stat-icon bg-primary-subtle text-primary mx-auto mb-2" style="width:64px;height:64px;font-size:1.6rem">
                    <?= strtoupper(substr($guest['first_name'],0,1)) ?>
                </div>
                <h5><?= e(trim($guest['first_name'].' '.$guest['last_name'])) ?></h5>
                <span class="badge bg-secondary text-capitalize"><?= e($guest['loyalty_tier']) ?></span>
                <span class="badge bg-info"><?= (int)$guest['loyalty_points'] ?> points</span>
                <hr>
                <div class="text-start small">
                    <div>📞 <?= e($guest['phone']) ?></div>
                    <div>✉ <?= e($guest['email'] ?: '—') ?></div>
                    <div>📍 <?= e(trim(($guest['city']??'').' '.($guest['country']??''))) ?: '—' ?></div>
                    <div>🪪 <?= e(ucwords(str_replace('_',' ',$guest['id_type'] ?? '—'))) ?>: <?= e($guest['id_number'] ?? '') ?></div>
                </div>
                <?php if (can('guests.manage')): ?>
                    <a href="<?= url('/guests/'.$guest['id'].'/edit') ?>" class="btn btn-sm btn-light mt-3 w-100">Edit</a>
                <?php endif; ?>
            </div>
        </div>
        <?php if ($guest['preferences'] || $guest['notes']): ?>
        <div class="card"><div class="card-body">
            <?php if ($guest['preferences']): ?><p class="small mb-1"><strong>Preferences:</strong> <?= e($guest['preferences']) ?></p><?php endif; ?>
            <?php if ($guest['notes']): ?><p class="small mb-0"><strong>Notes:</strong> <?= e($guest['notes']) ?></p><?php endif; ?>
        </div></div>
        <?php endif; ?>
    </div>
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">Stay History (<?= count($history) ?>)</div>
            <div class="table-responsive"><table class="table mb-0 align-middle">
                <thead><tr><th>Code</th><th>Check-in</th><th>Check-out</th><th>Amount</th><th>Status</th></tr></thead>
                <tbody>
                <?php if (!$history): ?><tr><td colspan="5" class="text-muted py-3 text-center">No stays yet.</td></tr><?php endif; ?>
                <?php foreach ($history as $h): ?>
                    <tr>
                        <td><a href="<?= url('/reservations/'.$h['id']) ?>"><?= e($h['code']) ?></a></td>
                        <td><?= dt($h['check_in']) ?></td>
                        <td><?= dt($h['check_out']) ?></td>
                        <td><?= money($h['total_amount']) ?></td>
                        <td><span class="badge bg-<?= badge($h['status']) ?>"><?= e(str_replace('_',' ',$h['status'])) ?></span></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table></div>
        </div>
    </div>
</div>
