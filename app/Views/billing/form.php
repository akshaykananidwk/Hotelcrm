<form method="post" action="<?= url('/billing') ?>">
    <?= csrf_field() ?>
    <?php if (!empty($reservationId)): ?>
        <input type="hidden" name="reservation_id" value="<?= e($reservationId) ?>">
    <?php endif; ?>
    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card mb-3">
                <div class="card-header d-flex justify-content-between">Line Items
                    <button type="button" class="btn btn-sm btn-light" onclick="addItem()">+ Add Item</button>
                </div>
                <div class="card-body"><div id="items"></div></div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">Invoice</div>
                <div class="card-body">
                    <label class="form-label">Guest</label>
                    <select name="guest_id" class="form-select mb-2">
                        <option value="">Walk-in</option>
                        <?php foreach ($guests as $g): ?>
                            <option value="<?= $g['id'] ?>"><?= e(trim($g['first_name'].' '.$g['last_name'])) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <label class="form-label">Discount</label>
                    <input name="discount" type="number" step="0.01" class="form-control mb-2" value="0">
                    <label class="form-label">GSTIN (customer)</label>
                    <input name="gst_number" class="form-control mb-2">
                    <label class="form-label">Notes</label>
                    <textarea name="notes" class="form-control mb-3" rows="2"></textarea>
                    <button class="btn btn-primary w-100">Create Invoice</button>
                </div>
            </div>
        </div>
    </div>
</form>

<template id="itemTpl">
    <div class="row g-2 mb-2 item-row">
        <div class="col-4"><input name="description[]" class="form-control form-control-sm" placeholder="Description"></div>
        <div class="col-2">
            <select name="category[]" class="form-select form-select-sm">
                <?php foreach (['room','restaurant','minibar','laundry','service','other'] as $c): ?>
                    <option value="<?= $c ?>"><?= ucfirst($c) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-2"><input name="quantity[]" type="number" step="0.01" value="1" class="form-control form-control-sm" placeholder="Qty"></div>
        <div class="col-2"><input name="unit_price[]" type="number" step="0.01" class="form-control form-control-sm" placeholder="Price"></div>
        <div class="col-1"><input name="tax_rate[]" type="number" step="0.01" value="12" class="form-control form-control-sm" placeholder="Tax%"></div>
        <div class="col-1"><button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('.item-row').remove()">✕</button></div>
    </div>
</template>
<?php \App\Core\View::startSection('scripts'); ?>
<script>
function addItem(){document.getElementById('items').appendChild(document.getElementById('itemTpl').content.cloneNode(true));}
addItem();
</script>
<?php \App\Core\View::endSection('scripts'); ?>
