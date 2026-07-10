<?php $h = $hotel ?? []; $isEdit = !empty($h['id']); ?>
<form method="post" action="<?= $isEdit ? url('/hotels/'.$h['id']) : url('/hotels') ?>">
    <?= csrf_field() ?>
    <?php if ($isEdit): ?><input type="hidden" name="_method" value="PUT"><?php endif; ?>
    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card mb-3">
                <div class="card-header">Hotel Profile</div>
                <div class="card-body row g-3">
                    <div class="col-md-6"><label class="form-label">Name *</label>
                        <input name="name" class="form-control" required value="<?= e($h['name'] ?? '') ?>"></div>
                    <div class="col-md-6"><label class="form-label">Legal name</label>
                        <input name="legal_name" class="form-control" value="<?= e($h['legal_name'] ?? '') ?>"></div>
                    <div class="col-md-6"><label class="form-label">Email</label>
                        <input name="email" type="email" class="form-control" value="<?= e($h['email'] ?? '') ?>"></div>
                    <div class="col-md-6"><label class="form-label">Phone</label>
                        <input name="phone" class="form-control" value="<?= e($h['phone'] ?? '') ?>"></div>
                    <div class="col-12"><label class="form-label">Address</label>
                        <input name="address" class="form-control" value="<?= e($h['address'] ?? '') ?>"></div>
                    <div class="col-md-4"><label class="form-label">City</label>
                        <input name="city" class="form-control" value="<?= e($h['city'] ?? '') ?>"></div>
                    <div class="col-md-4"><label class="form-label">State</label>
                        <input name="state" class="form-control" value="<?= e($h['state'] ?? '') ?>"></div>
                    <div class="col-md-4"><label class="form-label">Pincode</label>
                        <input name="pincode" class="form-control" value="<?= e($h['pincode'] ?? '') ?>"></div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card mb-3">
                <div class="card-header">Tax & Locale</div>
                <div class="card-body">
                    <label class="form-label">GSTIN</label>
                    <input name="gst_number" class="form-control mb-2" value="<?= e($h['gst_number'] ?? '') ?>">
                    <label class="form-label">PAN</label>
                    <input name="pan_number" class="form-control mb-2" value="<?= e($h['pan_number'] ?? '') ?>">
                    <div class="row g-2">
                        <div class="col-6"><label class="form-label">Currency</label>
                            <input name="currency" class="form-control" value="<?= e($h['currency'] ?? 'INR') ?>"></div>
                        <div class="col-6"><label class="form-label">Symbol</label>
                            <input name="currency_symbol" class="form-control" value="<?= e($h['currency_symbol'] ?? '₹') ?>"></div>
                    </div>
                    <label class="form-label mt-2">Timezone</label>
                    <input name="timezone" class="form-control mb-2" value="<?= e($h['timezone'] ?? 'Asia/Kolkata') ?>">
                    <div class="row g-2">
                        <div class="col-6"><label class="form-label">Check-in</label>
                            <input name="check_in_time" type="time" class="form-control" value="<?= e($h['check_in_time'] ?? '14:00') ?>"></div>
                        <div class="col-6"><label class="form-label">Check-out</label>
                            <input name="check_out_time" type="time" class="form-control" value="<?= e($h['check_out_time'] ?? '11:00') ?>"></div>
                    </div>
                    <button class="btn btn-primary w-100 mt-3"><?= $isEdit ? 'Update' : 'Create' ?> Hotel</button>
                </div>
            </div>
        </div>
    </div>
</form>
