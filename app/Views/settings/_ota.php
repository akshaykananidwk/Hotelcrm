<?php /** OTA credential grid. */ ?>
<form method="post" action="<?= url('/settings/ota') ?>">
    <?= csrf_field() ?>
    <p class="small text-muted">Enter partner API credentials per channel. Channels stay disabled until enabled here. Where an OTA requires partner approval, add the credentials once granted.</p>
    <div class="accordion" id="otaAcc">
        <?php foreach ($channels as $i => $c): ?>
            <?php $creds = json_decode($c['credentials'] ?? '{}', true) ?: []; ?>
            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#ota<?= $i ?>">
                        <?= e($c['display_name']) ?>
                        <span class="badge bg-<?= $c['is_enabled']?'success':'secondary' ?> ms-2"><?= $c['is_enabled']?'Enabled':'Off' ?></span>
                    </button>
                </h2>
                <div id="ota<?= $i ?>" class="accordion-collapse collapse" data-bs-parent="#otaAcc">
                    <div class="accordion-body row g-2">
                        <div class="col-md-4"><label class="form-label small">API Key</label>
                            <input name="channel[<?= e($c['channel']) ?>][api_key]" class="form-control form-control-sm" value="<?= e($creds['api_key'] ?? '') ?>"></div>
                        <div class="col-md-4"><label class="form-label small">Username</label>
                            <input name="channel[<?= e($c['channel']) ?>][username]" class="form-control form-control-sm" value="<?= e($creds['username'] ?? '') ?>"></div>
                        <div class="col-md-4"><label class="form-label small">Password</label>
                            <input type="password" name="channel[<?= e($c['channel']) ?>][password]" class="form-control form-control-sm"></div>
                        <div class="col-md-4"><label class="form-label small">Property/Hotel ID</label>
                            <input name="channel[<?= e($c['channel']) ?>][hotel_id]" class="form-control form-control-sm" value="<?= e($creds['hotel_id'] ?? '') ?>"></div>
                        <div class="col-md-4"><label class="form-label small">Endpoint URL</label>
                            <input name="channel[<?= e($c['channel']) ?>][endpoint]" class="form-control form-control-sm" value="<?= e($creds['endpoint'] ?? '') ?>"></div>
                        <div class="col-md-4 d-flex align-items-end">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="channel[<?= e($c['channel']) ?>][enabled]" value="1" <?= $c['is_enabled']?'checked':'' ?>>
                                <label class="form-check-label">Enabled</label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <button class="btn btn-primary mt-3">Save OTA Credentials</button>
</form>
