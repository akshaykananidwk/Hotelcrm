<?php $g = $guest ?? []; $isEdit = !empty($g['id']); ?>
<form method="post" action="<?= $isEdit ? url('/guests/'.$g['id']) : url('/guests') ?>" enctype="multipart/form-data">
    <?= csrf_field() ?>
    <?php if ($isEdit): ?><input type="hidden" name="_method" value="PUT"><?php endif; ?>
    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card mb-3">
                <div class="card-header">Guest Details</div>
                <div class="card-body row g-3">
                    <div class="col-md-6"><label class="form-label">First name *</label>
                        <input name="first_name" class="form-control" required value="<?= e($g['first_name'] ?? '') ?>"></div>
                    <div class="col-md-6"><label class="form-label">Last name</label>
                        <input name="last_name" class="form-control" value="<?= e($g['last_name'] ?? '') ?>"></div>
                    <div class="col-md-6"><label class="form-label">Phone *</label>
                        <input name="phone" class="form-control" required value="<?= e($g['phone'] ?? '') ?>"></div>
                    <div class="col-md-6"><label class="form-label">Email</label>
                        <input name="email" type="email" class="form-control" value="<?= e($g['email'] ?? '') ?>"></div>
                    <div class="col-md-4"><label class="form-label">City</label>
                        <input name="city" class="form-control" value="<?= e($g['city'] ?? '') ?>"></div>
                    <div class="col-md-4"><label class="form-label">Country</label>
                        <input name="country" class="form-control" value="<?= e($g['country'] ?? 'India') ?>"></div>
                    <div class="col-md-4"><label class="form-label">Gender</label>
                        <select name="gender" class="form-select">
                            <option value="">—</option>
                            <?php foreach (['male','female','other'] as $x): ?>
                                <option value="<?= $x ?>" <?= ($g['gender']??'')===$x?'selected':'' ?>><?= ucfirst($x) ?></option>
                            <?php endforeach; ?>
                        </select></div>
                    <div class="col-12"><label class="form-label">Address</label>
                        <input name="address" class="form-control" value="<?= e($g['address'] ?? '') ?>"></div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card mb-3">
                <div class="card-header">Identity (KYC)</div>
                <div class="card-body">
                    <label class="form-label">ID type</label>
                    <select name="id_type" class="form-select mb-2">
                        <option value="">—</option>
                        <?php foreach (['aadhaar','passport','pan','driving_license','voter_id','other'] as $x): ?>
                            <option value="<?= $x ?>" <?= ($g['id_type']??'')===$x?'selected':'' ?>><?= ucwords(str_replace('_',' ',$x)) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <input name="id_number" class="form-control mb-2" placeholder="ID number" value="<?= e($g['id_number'] ?? '') ?>">
                    <label class="form-label">ID document (image/PDF)</label>
                    <input name="id_document" type="file" class="form-control mb-2" accept=".jpg,.jpeg,.png,.pdf">
                    <input name="nationality" class="form-control mb-2" placeholder="Nationality" value="<?= e($g['nationality'] ?? '') ?>">
                </div>
            </div>
            <div class="card">
                <div class="card-header">Preferences & Notes</div>
                <div class="card-body">
                    <textarea name="preferences" class="form-control mb-2" rows="2" placeholder="Preferences"><?= e($g['preferences'] ?? '') ?></textarea>
                    <textarea name="notes" class="form-control mb-2" rows="2" placeholder="Internal notes"><?= e($g['notes'] ?? '') ?></textarea>
                    <button class="btn btn-primary w-100"><?= $isEdit ? 'Update' : 'Create' ?> Guest</button>
                </div>
            </div>
        </div>
    </div>
</form>
