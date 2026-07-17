<?php
/** OTA credential + automation grid. */
use App\Core\Crypto;
// Channels that have no small-hotel API and are driven via the web extranet.
$webAuto = ['makemytrip', 'goibibo'];
?>
<form method="post" action="<?= url('/settings/ota') ?>">
    <?= csrf_field() ?>
    <p class="small text-muted">
        Enter partner API credentials per channel, or — for OTAs that don't grant API access
        (e.g. MakeMyTrip / Goibibo) — switch <strong>Connection mode</strong> to
        <em>Web automation</em> and provide the extranet login. Credentials are encrypted at rest.
    </p>
    <div class="alert alert-warning small mb-3">
        <strong>Note on web automation:</strong> automating an extranet may be restricted by the OTA's
        Terms of Service and can break if the site changes or shows a CAPTCHA/OTP. Use your own
        account only. The login/inventory/booking <em>flows</em> are configurable JSON — confirm the
        selectors against the live portal, then use “Test” on the Channel Manager page.
    </div>
    <div class="accordion" id="otaAcc">
        <?php foreach ($channels as $i => $c): ?>
            <?php
            $creds = [];
            if (!empty($c['credentials'])) {
                $creds = Crypto::isEncrypted($c['credentials'])
                    ? Crypto::decryptArray($c['credentials'])
                    : (json_decode($c['credentials'], true) ?: []);
            }
            $auto = [];
            if (!empty($c['automation_config'])) {
                $auto = Crypto::isEncrypted($c['automation_config'])
                    ? Crypto::decryptArray($c['automation_config'])
                    : (json_decode($c['automation_config'], true) ?: []);
            }
            $mode = $c['connection_mode'] ?? 'api';
            $isWeb = in_array($c['channel'], $webAuto, true);
            $ch = e($c['channel']);
            ?>
            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#ota<?= $i ?>">
                        <?= e($c['display_name']) ?>
                        <span class="badge bg-<?= $c['is_enabled']?'success':'secondary' ?> ms-2"><?= $c['is_enabled']?'Enabled':'Off' ?></span>
                        <?php if ($isWeb): ?><span class="badge bg-info ms-2">No API — web automation</span><?php endif; ?>
                    </button>
                </h2>
                <div id="ota<?= $i ?>" class="accordion-collapse collapse" data-bs-parent="#otaAcc">
                    <div class="accordion-body">
                        <div class="row g-2">
                            <div class="col-md-4">
                                <label class="form-label small">Connection mode</label>
                                <select name="channel[<?= $ch ?>][mode]" class="form-select form-select-sm">
                                    <option value="api" <?= $mode==='api'?'selected':'' ?>>Official API</option>
                                    <option value="web_session" <?= $mode==='web_session'?'selected':'' ?>>Web session (cURL)</option>
                                    <option value="browser" <?= $mode==='browser'?'selected':'' ?>>Browser automation</option>
                                </select>
                            </div>
                            <div class="col-md-4"><label class="form-label small">Extranet username / login</label>
                                <input name="channel[<?= $ch ?>][username]" class="form-control form-control-sm" value="<?= e($creds['username'] ?? '') ?>"></div>
                            <div class="col-md-4"><label class="form-label small">Password</label>
                                <input type="password" name="channel[<?= $ch ?>][password]" class="form-control form-control-sm" placeholder="<?= !empty($creds['password'])?'•••••• (saved)':'' ?>"></div>
                            <div class="col-md-4"><label class="form-label small">API Key (API mode)</label>
                                <input name="channel[<?= $ch ?>][api_key]" class="form-control form-control-sm" value="<?= e($creds['api_key'] ?? '') ?>"></div>
                            <div class="col-md-4"><label class="form-label small">Property / Hotel ID</label>
                                <input name="channel[<?= $ch ?>][hotel_id]" class="form-control form-control-sm" value="<?= e($creds['hotel_id'] ?? '') ?>"></div>
                            <div class="col-md-4"><label class="form-label small">Login / API URL</label>
                                <input name="channel[<?= $ch ?>][endpoint]" class="form-control form-control-sm" value="<?= e($creds['endpoint'] ?? '') ?>"></div>
                            <div class="col-12">
                                <label class="form-label small">Automation config (JSON — driver, login_url, selectors, flows)</label>
                                <textarea name="channel[<?= $ch ?>][automation_json]" class="form-control form-control-sm font-monospace" rows="5"
                                    placeholder='{"mode":"browser","driver_url":"http://127.0.0.1:9515","login_url":"https://ingommt.makemytrip.com/","vars":{"post_login_url_contains":"dashboard","inventory_url":"...","avail_field":"#avail","stop_sell_field":"#stopSell","inventory_save":"#save","bookings_url":"...","booking_row":".bk","booking_ref_cell":".ref","booking_guest_cell":".guest"},"flows":{}}'><?= e($auto ? json_encode($auto, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) : '') ?></textarea>
                                <div class="form-text">Leave blank to use the built-in template. Override <code>vars</code> with the real selectors from your extranet, or supply full <code>flows</code>.</div>
                            </div>
                            <div class="col-12">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="channel[<?= $ch ?>][enabled]" value="1" <?= $c['is_enabled']?'checked':'' ?>>
                                    <label class="form-check-label">Enabled</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <button class="btn btn-primary mt-3">Save OTA Settings</button>
    <a href="<?= url('/channels') ?>" class="btn btn-light mt-3">Go to Channel Manager (Sync / Test)</a>
</form>
