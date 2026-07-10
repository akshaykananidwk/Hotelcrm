<?php
namespace App\Controllers\Api;

use App\Core\Controller;
use App\Core\Auth;

/** Base REST controller: JSON responses + hotel scoping from the token owner. */
class ApiController extends Controller
{
    protected function hotelId(): int
    {
        return Auth::hotelId() ?? (int) \App\Core\App::db()->scalar('SELECT id FROM hotels ORDER BY id LIMIT 1');
    }

    protected function success($data = null, int $status = 200): void
    {
        $this->json(['success' => true, 'data' => $data], $status);
    }

    protected function error(string $message, int $status = 400, $details = null): void
    {
        $this->json(['success' => false, 'error' => $message, 'details' => $details], $status);
    }
}
