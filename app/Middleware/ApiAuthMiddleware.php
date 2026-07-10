<?php
namespace App\Middleware;

use App\Core\Auth;
use App\Core\Middleware;
use App\Core\Request;
use App\Core\RateLimiter;

/** Guards REST API routes with bearer-token auth + rate limiting. */
class ApiAuthMiddleware extends Middleware
{
    public function handle(): bool
    {
        header('Content-Type: application/json');

        // Rate limit per client IP.
        if (!RateLimiter::allow('api:' . Request::ip())) {
            http_response_code(429);
            echo json_encode(['error' => 'Too many requests']);
            return false;
        }

        $token = Request::bearerToken();
        if (!$token || !Auth::fromApiToken($token)) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized. Provide a valid API token.']);
            return false;
        }
        return true;
    }
}
