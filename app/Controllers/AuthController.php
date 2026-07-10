<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Session;
use App\Core\Request;
use App\Core\RateLimiter;
use App\Core\Audit;
use App\Core\App;

class AuthController extends Controller
{
    /** Redirect root to dashboard or login. */
    public function root(): void
    {
        $this->redirect(Auth::check() ? '/dashboard' : '/login');
    }

    public function showLogin(): void
    {
        if (Auth::check()) {
            $this->redirect('/dashboard');
        }
        echo \App\Core\View::render('auth/login', [
            'flashes' => Session::getFlashes(),
            'csrfToken' => \App\Core\Csrf::token(),
        ], 'auth');
    }

    public function login(): void
    {
        $email = trim((string) Request::post('email'));
        $password = (string) Request::post('password');

        // Rate-limit / brute-force protection per IP + email.
        $key = 'login:' . Request::ip() . ':' . $email;
        $max = App::config('security.max_login_try', 5);
        if (!RateLimiter::allow($key, $max, App::config('security.lockout_time', 900))) {
            $this->logAttempt($email, false);
            Session::flash('error', 'Too many failed attempts. Please try again later.');
            $this->redirect('/login');
        }

        $user = Auth::attempt($email, $password);
        if (!$user) {
            $this->logAttempt($email, false);
            Session::flash('error', 'Invalid email or password.');
            $this->redirect('/login');
        }

        RateLimiter::reset($key);
        Auth::login($user);
        $this->logAttempt($email, true, (int) $user['id']);
        Audit::log('auth.login', 'user', $user['id']);
        $this->redirect('/dashboard');
    }

    public function logout(): void
    {
        Audit::log('auth.logout', 'user', Auth::id());
        Auth::logout();
        $this->redirect('/login');
    }

    private function logAttempt(string $email, bool $success, ?int $userId = null): void
    {
        App::db()->insert('login_logs', [
            'email' => $email,
            'user_id' => $userId,
            'success' => $success ? 1 : 0,
            'ip_address' => Request::ip(),
            'user_agent' => substr(Request::userAgent(), 0, 255),
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }
}
