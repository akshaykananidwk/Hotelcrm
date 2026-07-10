<?php
namespace App\Core;

/** Base controller with shared render / redirect / JSON helpers. */
class Controller
{
    /** Render an HTML view within the admin layout. */
    protected function view(string $view, array $data = [], ?string $layout = 'app'): void
    {
        // Expose common globals to every view.
        $data = array_merge([
            'authUser'   => Auth::user(),
            'flashes'    => Session::getFlashes(),
            'csrfToken'  => Csrf::token(),
            'currentUri' => Request::uri(),
        ], $data);
        echo View::render($view, $data, $layout);
    }

    /** Public alias used by the router for error pages. */
    public function render(string $view, array $data = [], ?string $layout = 'app'): void
    {
        $this->view($view, $data, $layout);
    }

    protected function json($data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data);
    }

    protected function redirect(string $path): void
    {
        header('Location: ' . App::baseUrl($path));
        exit;
    }

    protected function back(): void
    {
        $ref = $_SERVER['HTTP_REFERER'] ?? App::baseUrl('/');
        header('Location: ' . $ref);
        exit;
    }

    /** Validate input against a Validator ruleset; redirect back on failure. */
    protected function validate(array $rules, ?array $data = null): array
    {
        $data = $data ?? Request::all();
        $validator = new Validator($data, $rules);
        if (!$validator->passes()) {
            if (Request::wantsJson()) {
                $this->json(['errors' => $validator->errors()], 422);
                exit;
            }
            Session::flash('error', implode(' ', $validator->flatErrors()));
            Session::set('_old', $data);
            $this->back();
        }
        return $validator->validated();
    }

    /** Ensure the current user holds a permission or abort. */
    protected function authorize(string $permission): void
    {
        if (!Auth::can($permission)) {
            http_response_code(403);
            if (Request::wantsJson()) {
                $this->json(['error' => 'Forbidden'], 403);
            } else {
                $this->view('errors/403', [], 'app');
            }
            exit;
        }
    }

    protected function old(string $key, $default = ''): string
    {
        $old = Session::get('_old', []);
        Session::forget('_old');
        return (string) ($old[$key] ?? $default);
    }

    /**
     * Hotel currently in scope. Staff are bound to their own hotel; a platform
     * super admin can switch hotels via ?hotel= (stored on the session).
     */
    protected function currentHotelId(): int
    {
        $bound = Auth::hotelId();
        if ($bound) {
            return $bound;
        }
        $selected = Request::get('hotel') ?? Session::get('active_hotel');
        if ($selected) {
            Session::set('active_hotel', (int) $selected);
            return (int) $selected;
        }
        $first = (int) (App::db()->scalar('SELECT id FROM hotels ORDER BY id ASC LIMIT 1') ?? 0);
        return $first;
    }

    protected function page(): int
    {
        return max(1, (int) Request::get('page', 1));
    }
}
