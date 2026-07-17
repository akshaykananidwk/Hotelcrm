<?php
// Mock OTA extranet for testing automation (login form + protected dashboard).
session_start();
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

if ($path === '/login' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // Require the CSRF token the login page embedded (tests token extraction).
    $ok = ($_POST['csrf'] ?? '') === ($_SESSION['csrf'] ?? '_')
        && ($_POST['username'] ?? '') === 'hoteluser'
        && ($_POST['password'] ?? '') === 'secret123';
    if ($ok) {
        $_SESSION['auth'] = true;
        header('Location: /dashboard');
        exit;
    }
    echo '<p id="err">Invalid credentials</p>';
    exit;
}

if ($path === '/dashboard') {
    if (empty($_SESSION['auth'])) { header('Location: /login'); exit; }
    // Protected page with inventory data + a bookings table + an inventory form.
    ?>
    <!doctype html><html><head><title>Extranet Dashboard</title></head><body>
    <h1 class="page-title">Property Dashboard</h1>
    <div id="welcome">Welcome, Grand Palace Hotel</div>
    <table id="bookings">
      <tr class="bk"><td class="ref">MMT-1001</td><td class="guest">A Sharma</td><td class="in">2026-08-01</td></tr>
      <tr class="bk"><td class="ref">MMT-1002</td><td class="guest">R Patel</td><td class="in">2026-08-03</td></tr>
    </table>
    <form id="invForm">
      <input id="roomAvail" name="avail" value="">
      <input id="roomRate" name="rate" value="">
      <select id="stopSell"><option value="0">Open</option><option value="1">Stop Sell</option></select>
      <button id="saveInv" type="button" onclick="document.getElementById('saveMsg').innerText='Saved '+document.getElementById('roomAvail').value+' @ '+document.getElementById('roomRate').value">Save</button>
    </form>
    <div id="saveMsg"></div>
    </body></html>
    <?php
    exit;
}

// Login page (GET /login or /)
$_SESSION['csrf'] = $_SESSION['csrf'] ?? bin2hex(random_bytes(8));
?>
<!doctype html><html><head><title>Extranet Login</title></head><body>
<h1>Partner Login</h1>
<form id="loginForm" method="post" action="/login">
  <input type="hidden" name="csrf" value="<?= $_SESSION['csrf'] ?>">
  <input id="username" name="username" placeholder="Username">
  <input id="password" name="password" type="password" placeholder="Password">
  <button id="loginBtn" type="submit">Sign In</button>
</form>
</body></html>
