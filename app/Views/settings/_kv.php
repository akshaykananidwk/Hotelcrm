<?php
/** Key/value settings form driven by a per-group field schema. */
$schema = [
    'smtp' => [
        'host' => 'SMTP Host', 'port' => 'Port', 'username' => 'Username',
        'password' => ['label' => 'Password', 'type' => 'password'],
        'from_email' => 'From Email', 'from_name' => 'From Name',
        'encryption' => ['label' => 'Encryption (tls/ssl)'],
    ],
    'whatsapp' => [
        'provider' => 'Provider', 'api_url' => 'API URL',
        'session_id' => ['label' => 'Session ID', 'type' => 'password'],
        'api_key' => ['label' => 'API Key', 'type' => 'password'],
    ],
    'payment' => [
        'default_gateway' => 'Default Gateway (cash/razorpay/payu/phonepe)',
        'razorpay_key' => 'Razorpay Key',
        'razorpay_secret' => ['label' => 'Razorpay Secret', 'type' => 'password'],
        'payu_key' => 'PayU Key',
        'payu_salt' => ['label' => 'PayU Salt', 'type' => 'password'],
        'phonepe_merchant_id' => 'PhonePe Merchant ID',
        'phonepe_salt' => ['label' => 'PhonePe Salt', 'type' => 'password'],
    ],
    'mikrotik' => [
        'host' => 'Router Host/IP', 'port' => 'API Port (8728)', 'username' => 'Username',
        'password' => ['label' => 'Password', 'type' => 'password'],
        'default_profile' => 'Default Hotspot Profile', 'bandwidth' => 'Bandwidth Limit (e.g. 5M/5M)',
    ],
    'company' => [
        'company_name' => 'Company Name', 'support_email' => 'Support Email',
        'support_phone' => 'Support Phone', 'invoice_prefix' => 'Invoice Prefix',
    ],
];
$fields = $schema[$group] ?? [];
?>
<form method="post" action="<?= url('/settings/'.$group) ?>">
    <?= csrf_field() ?>
    <div class="row g-3">
        <?php foreach ($fields as $key => $def): ?>
            <?php
            $label = is_array($def) ? ($def['label'] ?? $key) : $def;
            $type = is_array($def) ? ($def['type'] ?? 'text') : 'text';
            $val = $values[$key] ?? '';
            ?>
            <div class="col-md-6">
                <label class="form-label"><?= e($label) ?></label>
                <input type="<?= $type ?>" name="settings[<?= e($key) ?>]" class="form-control"
                       value="<?= $type==='password' ? '' : e($val) ?>"
                       placeholder="<?= $type==='password' && $val ? '•••••• (saved)' : '' ?>">
            </div>
        <?php endforeach; ?>
    </div>
    <button class="btn btn-primary mt-3">Save Settings</button>
</form>
<?php if ($group === 'whatsapp'): ?>
    <hr>
    <p class="small text-muted mb-0">
        Uses the bulk.akdwk.in gateway. Text: <code>api.php?number=91XXXXXXXXXX&amp;message=...&amp;session_id=...&amp;api_key=...</code>
    </p>
<?php endif; ?>
