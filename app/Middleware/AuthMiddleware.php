<?php
namespace App\Middleware;

use App\Core\Auth;
use App\Core\Middleware;
use App\Core\App;

/** Guards admin routes: redirect guests to login. */
class AuthMiddleware extends Middleware
{
    public function handle(): bool
    {
        if (!Auth::check()) {
            header('Location: ' . App::baseUrl('/login'));
            return false;
        }
        return true;
    }
}
