<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0">Invoices</h5>
    <?php if (can('billing.manage')): ?>
        <a href="<?= url('/billing/create') ?>" class="btn btn-sm btn-primary">+ New Invoice</a>
    <?php endif; ?>
</div>
<div class="card"><div class="table-responsive">
<table class="table table-hover align-middle mb-0">
    <thead><tr><th>Number</th><th>Guest</th><th>Date</th><th>Total</th><th>Paid</th><th>Balance</th><th>Status</th><th></th></tr></thead>
    <tbody>
    <?php if (!$invoices): ?><tr><td colspan="8" class="text-center text-muted py-4">No invoices.</td></tr><?php endif; ?>
    <?php foreach ($invoices as $i): ?>
        <tr>
            <td><a href="<?= url('/billing/'.$i['id']) ?>"><?= e($i['number']) ?></a></td>
            <td><?= e($i['guest_name'] ?: 'Walk-in') ?></td>
            <td><?= dt($i['issued_at']) ?></td>
            <td><?= money($i['total']) ?></td>
            <td class="text-success"><?= money($i['paid']) ?></td>
            <td class="text-danger"><?= money($i['balance']) ?></td>
            <td><span class="badge bg-<?= badge($i['status']) ?>"><?= e($i['status']) ?></span></td>
            <td class="text-end">
                <a href="<?= url('/billing/'.$i['id'].'/pdf') ?>" class="btn btn-sm btn-light" target="_blank">PDF</a>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div></div>
<?= \App\Core\View::renderPartial('layouts/_pagination', ['pg' => $pg, 'baseUrl' => url('/billing')]) ?>
