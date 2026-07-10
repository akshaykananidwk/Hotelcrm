<?php
/**
 * HotelCRM ERP — Installation Wizard
 *
 * Self-contained first-run installer. Checks server requirements, collects DB
 * credentials, writes config/config.php, imports the schema + seed data and
 * creates the initial admin. Delete the /install directory (or the created
 * installed.lock) once finished.
 */

$root = dirname(__DIR__);
$lock = __DIR__ . '/installed.lock';
$configFile = $root . '/config/config.php';

session_start();
$step = $_GET['step'] ?? 'requirements';
$errors = [];
$success = null;

// Guard: already installed.
if (is_file($lock) && $step !== 'done') {
    $step = 'locked';
}

/** Server requirement checks. */
function requirements(): array
{
    return [
        'PHP >= 8.1' => version_compare(PHP_VERSION, '8.1.0', '>='),
        'PDO MySQL extension' => extension_loaded('pdo_mysql'),
        'cURL extension' => extension_loaded('curl'),
        'mbstring extension' => extension_loaded('mbstring'),
        'fileinfo extension' => extension_loaded('fileinfo'),
        'openssl extension' => extension_loaded('openssl'),
        'config/ writable' => is_writable(dirname(__DIR__) . '/config'),
        'storage/ writable' => is_writable(dirname(__DIR__) . '/storage'),
    ];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $step === 'database') {
    $db = [
        'host' => trim($_POST['db_host'] ?? '127.0.0.1'),
        'port' => (int) ($_POST['db_port'] ?? 3306),
        'name' => trim($_POST['db_name'] ?? ''),
        'user' => trim($_POST['db_user'] ?? ''),
        'pass' => $_POST['db_pass'] ?? '',
        'charset' => 'utf8mb4',
    ];
    $appUrl = rtrim(trim($_POST['app_url'] ?? ''), '/');
    $adminName = trim($_POST['admin_name'] ?? '');
    $adminEmail = trim($_POST['admin_email'] ?? '');
    $adminPass = $_POST['admin_pass'] ?? '';

    if ($db['name'] === '' || $db['user'] === '') {
        $errors[] = 'Database name and user are required.';
    }
    if ($adminEmail === '' || strlen($adminPass) < 8) {
        $errors[] = 'Admin email and a password of at least 8 characters are required.';
    }

    if (!$errors) {
        try {
            $dsn = "mysql:host={$db['host']};port={$db['port']};charset=utf8mb4";
            $pdo = new PDO($dsn, $db['user'], $db['pass'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$db['name']}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $pdo->exec("USE `{$db['name']}`");

            // Import schema + seed.
            $pdo->exec(file_get_contents($root . '/database/schema.sql'));
            $pdo->exec(file_get_contents($root . '/database/seed.sql'));

            // Replace seeded super admin with the installer-provided admin.
            $hash = password_hash($adminPass, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare('UPDATE users SET name = ?, email = ?, password = ? WHERE id = 1');
            $stmt->execute([$adminName ?: 'Super Admin', $adminEmail, $hash]);

            // Write config.php from the sample.
            $config = file_get_contents($root . '/config/config.sample.php');
            $config = str_replace(
                ["'127.0.0.1'", "3306", "'hotelcrm'", "'root'", "'change-this-to-a-long-random-string'", "'http://localhost'", "'env'       => 'production'"],
                ["'{$db['host']}'", (string) $db['port'], "'{$db['name']}'", "'{$db['user']}'", "'" . bin2hex(random_bytes(24)) . "'", "'{$appUrl}'", "'env'       => 'production'"],
                $config
            );
            // Inject the DB password (kept out of the string-replace above for safety).
            $config = preg_replace("/'pass'    => '',/", "'pass'    => " . var_export($db['pass'], true) . ",", $config, 1);
            file_put_contents($configFile, $config);

            file_put_contents($lock, date('c'));
            $step = 'done';
            $success = true;
        } catch (Throwable $e) {
            $errors[] = 'Installation failed: ' . $e->getMessage();
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Install · HotelCRM ERP</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<style>body{background:linear-gradient(135deg,#0d6efd,#6610f2);min-height:100vh}.wrap{max-width:640px;margin:3rem auto}</style>
</head>
<body>
<div class="wrap">
    <div class="card shadow-lg">
        <div class="card-body p-4">
            <h3 class="mb-1">HotelCRM ERP</h3>
            <p class="text-muted">Installation Wizard</p>

            <?php foreach ($errors as $e): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($e) ?></div>
            <?php endforeach; ?>

            <?php if ($step === 'locked'): ?>
                <div class="alert alert-warning">The application is already installed. Delete
                    <code>install/installed.lock</code> to run the wizard again.</div>
                <a href="../" class="btn btn-primary">Go to Login</a>

            <?php elseif ($step === 'done'): ?>
                <div class="alert alert-success">Installation complete! 🎉</div>
                <p>For security, delete the <code>/install</code> directory now.</p>
                <a href="../" class="btn btn-primary">Go to Login</a>

            <?php elseif ($step === 'database'): ?>
                <form method="post" action="?step=database">
                    <h5 class="mt-2">Database</h5>
                    <div class="row g-2">
                        <div class="col-8"><label class="form-label">Host</label><input name="db_host" class="form-control" value="127.0.0.1"></div>
                        <div class="col-4"><label class="form-label">Port</label><input name="db_port" class="form-control" value="3306"></div>
                        <div class="col-12"><label class="form-label">Database name</label><input name="db_name" class="form-control" value="hotelcrm" required></div>
                        <div class="col-6"><label class="form-label">User</label><input name="db_user" class="form-control" value="root" required></div>
                        <div class="col-6"><label class="form-label">Password</label><input name="db_pass" type="password" class="form-control"></div>
                    </div>
                    <h5 class="mt-4">Application</h5>
                    <label class="form-label">Base URL</label>
                    <input name="app_url" class="form-control" value="http://<?= htmlspecialchars($_SERVER['HTTP_HOST'] ?? 'localhost') ?>">
                    <h5 class="mt-4">Administrator</h5>
                    <div class="row g-2">
                        <div class="col-12"><label class="form-label">Name</label><input name="admin_name" class="form-control" value="Super Admin"></div>
                        <div class="col-6"><label class="form-label">Email</label><input name="admin_email" type="email" class="form-control" required></div>
                        <div class="col-6"><label class="form-label">Password</label><input name="admin_pass" type="password" class="form-control" required></div>
                    </div>
                    <button class="btn btn-primary mt-4 w-100">Install Now</button>
                </form>

            <?php else: ?>
                <h5>Server Requirements</h5>
                <ul class="list-group mb-3">
                    <?php $allOk = true; foreach (requirements() as $label => $ok): $allOk = $allOk && $ok; ?>
                        <li class="list-group-item d-flex justify-content-between">
                            <?= htmlspecialchars($label) ?>
                            <span class="badge bg-<?= $ok ? 'success' : 'danger' ?>"><?= $ok ? 'OK' : 'Missing' ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <?php if ($allOk): ?>
                    <a href="?step=database" class="btn btn-primary w-100">Continue</a>
                <?php else: ?>
                    <div class="alert alert-warning">Please resolve the missing requirements, then reload.</div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
    <p class="text-center text-white-50 mt-3 small">HotelCRM ERP + PMS + Channel Manager</p>
</div>
</body>
</html>
