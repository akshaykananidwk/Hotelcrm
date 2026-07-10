<?php
namespace App\Core;

/**
 * Minimal but capable HTTP router. Supports named parameters ({id}), HTTP
 * verbs, per-route middleware and a group prefix for the API namespace.
 */
class Router
{
    private array $routes = [];
    private array $groupStack = [];

    public function get(string $path, $handler, array $middleware = []): void
    {
        $this->add('GET', $path, $handler, $middleware);
    }
    public function post(string $path, $handler, array $middleware = []): void
    {
        $this->add('POST', $path, $handler, $middleware);
    }
    public function put(string $path, $handler, array $middleware = []): void
    {
        $this->add('PUT', $path, $handler, $middleware);
    }
    public function delete(string $path, $handler, array $middleware = []): void
    {
        $this->add('DELETE', $path, $handler, $middleware);
    }

    /** Group routes under a shared prefix / middleware set. */
    public function group(array $attrs, callable $callback): void
    {
        $this->groupStack[] = $attrs;
        $callback($this);
        array_pop($this->groupStack);
    }

    private function add(string $method, string $path, $handler, array $middleware): void
    {
        $prefix = '';
        $groupMw = [];
        foreach ($this->groupStack as $g) {
            $prefix .= $g['prefix'] ?? '';
            $groupMw = array_merge($groupMw, $g['middleware'] ?? []);
        }
        $full = '/' . trim($prefix . '/' . trim($path, '/'), '/');
        $this->routes[] = [
            'method'     => $method,
            'path'       => $full === '//' ? '/' : $full,
            'handler'    => $handler,
            'middleware' => array_merge($groupMw, $middleware),
        ];
    }

    /** Dispatch the current request. */
    public function dispatch(string $method, string $uri): void
    {
        $uri = '/' . trim(parse_url($uri, PHP_URL_PATH), '/');
        if ($uri === '/') {
            $uri = '/';
        }
        // Method override for HTML forms (PUT/DELETE via _method).
        if ($method === 'POST' && isset($_POST['_method'])) {
            $method = strtoupper($_POST['_method']);
        }

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }
            $pattern = preg_replace('#\{([a-zA-Z_]+)\}#', '(?P<$1>[^/]+)', $route['path']);
            if (preg_match('#^' . $pattern . '$#', $uri, $matches)) {
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);

                // Run middleware.
                foreach ($route['middleware'] as $mw) {
                    $instance = new $mw();
                    if ($instance->handle() === false) {
                        return; // middleware halted the request
                    }
                }

                $this->invoke($route['handler'], $params);
                return;
            }
        }

        $this->notFound();
    }

    private function invoke($handler, array $params): void
    {
        if (is_callable($handler)) {
            echo $handler($params);
            return;
        }
        [$controller, $action] = explode('@', $handler);
        $class = "App\\Controllers\\$controller";
        if (!class_exists($class)) {
            $this->notFound();
            return;
        }
        $instance = new $class();
        call_user_func_array([$instance, $action], [$params]);
    }

    private function notFound(): void
    {
        http_response_code(404);
        if (Request::wantsJson()) {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Not found']);
            return;
        }
        (new Controller())->render('errors/404', [], null);
    }
}
