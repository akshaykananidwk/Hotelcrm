<div class="row g-3">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h4><?= e($hotel['name']) ?></h4>
                        <div class="small text-muted"><?= e($hotel['address']) ?>, <?= e($hotel['city']) ?></div>
                        <div class="small text-muted">GSTIN: <?= e($hotel['gst_number'] ?? '—') ?></div>
                    </div>
                    <div class="text-end">
                        <h5>TAX INVOICE</h5>
                        <div><?= e($invoice['number']) ?></div>
                        <div class="small text-muted"><?= dt($invoice['issued_at']) ?></div>
                        <span class="badge bg-<?= badge($invoice['status']) ?>"><?= e($invoice['status']) ?></span>
                    </div>
                </div>
                <hr>
                <div class="mb-3"><strong>Bill To:</strong>
                    <?= $guest ? e(trim($guest['first_name'].' '.$guest['last_name'])) : 'Walk-in Guest' ?>
                    <?php if ($guest && $guest['phone']): ?><span class="text-muted">· <?= e($guest['phone']) ?></span><?php endif; ?>
                </div>
                <div class="table-responsive"><table class="table">
                    <thead><tr><th>Description</th><th>Qty</th><th>Rate</th><th>Tax</th><th class="text-end">Total</th></tr></thead>
                    <tbody>
                    <?php foreach ($items as $it): ?>
                        <tr>
                            <td><?= e($it['description']) ?><br><small class="text-muted text-capitalize"><?= e($it['category']) ?></small></td>
                            <td><?= e($it['quantity']) ?></td>
                            <td><?= money($it['unit_price']) ?></td>
                            <td><?= e($it['tax_rate']) ?>% (<?= money($it['tax_amount']) ?>)</td>
                            <td class="text-end"><?= money($it['total']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr><td colspan="4" class="text-end">Subtotal</td><td class="text-end"><?= money($invoice['subtotal']) ?></td></tr>
                        <tr><td colspan="4" class="text-end">Tax</td><td class="text-end"><?= money($invoice['tax_total']) ?></td></tr>
                        <tr><td colspan="4" class="text-end">Discount</td><td class="text-end">- <?= money($invoice['discount']) ?></td></tr>
                        <tr class="fw-bold"><td colspan="4" class="text-end">Total</td><td class="text-end"><?= money($invoice['total']) ?></td></tr>
                        <tr class="text-success"><td colspan="4" class="text-end">Paid</td><td class="text-end"><?= money($invoice['paid']) ?></td></tr>
                        <tr class="text-danger fw-bold"><td colspan="4" class="text-end">Balance</td><td class="text-end"><?= money($invoice['balance']) ?></td></tr>
                    </tfoot>
                </table></div>
                <div class="d-flex gap-2">
                    <a href="<?= url('/billing/'.$invoice['id'].'/pdf') ?>" class="btn btn-outline-primary" target="_blank">Download PDF</a>
                    <?php if (can('billing.manage')): ?>
                    <form method="post" action="<?= url('/billing/'.$invoice['id'].'/email') ?>">
                        <?= csrf_field() ?><button class="btn btn-outline-secondary">Email Invoice</button>
                    </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <?php if (can('billing.manage') && $invoice['status'] !== 'paid'): ?>
        <div class="card mb-3">
            <div class="card-header">Record Payment</div>
            <div class="card-body">
                <form method="post" action="<?= url('/billing/'.$invoice['id'].'/payment') ?>">
                    <?= csrf_field() ?>
                    <input name="amount" type="number" step="0.01" class="form-control mb-2" placeholder="Amount"
                           value="<?= e($invoice['balance']) ?>" required>
                    <select name="method" class="form-select mb-2">
                        <?php foreach (['cash','card','upi','bank_transfer','razorpay','phonepe','payu'] as $m): ?>
                            <option value="<?= $m ?>"><?= ucwords(str_replace('_',' ',$m)) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <select name="type" class="form-select mb-2">
                        <option value="payment">Payment</option>
                        <option value="refund">Refund</option>
                    </select>
                    <input name="gateway_ref" class="form-control mb-2" placeholder="Reference (optional)">
                    <button class="btn btn-primary w-100">Record</button>
                </form>
            </div>
        </div>
        <?php endif; ?>
        <div class="card">
            <div class="card-header">Payment History</div>
            <div class="list-group list-group-flush">
                <?php if (!$payments): ?><div class="list-group-item text-muted small">No payments.</div><?php endif; ?>
                <?php foreach ($payments as $p): ?>
                    <div class="list-group-item d-flex justify-content-between">
                        <span><?= dt($p['created_at'],'d M H:i') ?><br><small class="text-muted text-capitalize"><?= e(str_replace('_',' ',$p['method'])) ?></small></span>
                        <span class="<?= $p['type']==='refund'?'text-danger':'text-success' ?>">
                            <?= $p['type']==='refund'?'-':'' ?><?= money($p['amount']) ?>
                        </span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>
