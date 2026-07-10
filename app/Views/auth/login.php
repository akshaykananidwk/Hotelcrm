<?php /** Login form. */ ?>
<div class="auth-card">
    <div class="auth-logo">H</div>
    <h1 class="h4 text-center mb-1"><?= e(config('app.name', 'HotelCRM ERP')) ?></h1>
    <p class="text-center text-muted mb-4">Sign in to your account</p>

    <form method="post" action="<?= url('/login') ?>">
        <?= csrf_field() ?>
        <div class="mb-3">
            <label class="form-label">Email</label>
            <input type="email" name="email" class="form-control" required autofocus
                   placeholder="admin@hotelcrm.test" value="<?= old('email') ?>">
        </div>
        <div class="mb-3">
            <label class="form-label">Password</label>
            <input type="password" name="password" class="form-control" required placeholder="••••••••">
        </div>
        <button type="submit" class="btn btn-primary w-100">Sign In</button>
    </form>

    <hr class="my-4">
    <p class="small text-muted text-center mb-0">
        Demo: <code>admin@hotelcrm.test</code> / <code>Admin@123</code>
    </p>
</div>
