<?php

declare(strict_types=1);

namespace WealthFlow\Http;

use Closure;

final class Router
{
    /** @var array<int, array{method: string, pattern: string, handler: Closure|array{object|string, string}}> */
    private array $routes = [];

    public function get(string $path, Closure|array $handler): void
    {
        $this->addRoute('GET', $path, $handler);
    }

    public function post(string $path, Closure|array $handler): void
    {
        $this->addRoute('POST', $path, $handler);
    }

    public function put(string $path, Closure|array $handler): void
    {
        $this->addRoute('PUT', $path, $handler);
    }

    public function delete(string $path, Closure|array $handler): void
    {
        $this->addRoute('DELETE', $path, $handler);
    }

    public function addRoute(string $method, string $path, Closure|array $handler): void
    {
        // Convert {id} placeholders to regex named groups
        $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<$1>[^/]+)', $path);
        $pattern = '#^' . $pattern . '$#';

        $this->routes[] = [
            'method' => strtoupper($method),
            'pattern' => $pattern,
            'handler' => $handler,
        ];
    }

    public function dispatch(Request $request): void
    {
        // CORS preflight
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, Idempotency-Key, X-User-Id');

        if ($request->getMethod() === 'OPTIONS') {
            http_response_code(204);
            return;
        }

        $method = $request->getMethod();
        $path = $request->getPath();

        // Normalize /api prefix if present
        $cleanPath = $path;
        if (str_starts_with($cleanPath, '/api/')) {
            $cleanPath = substr($cleanPath, 4); // Keep leading /
        } elseif ($cleanPath === '/api') {
            $cleanPath = '/';
        }

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            if (preg_match($route['pattern'], $cleanPath, $matches)) {
                $params = [];
                foreach ($matches as $k => $v) {
                    if (is_string($k)) {
                        $params[$k] = is_numeric($v) ? (int) $v : $v;
                    }
                }

                $handler = $route['handler'];
                if ($handler instanceof Closure) {
                    $handler($request, ...array_values($params));
                    return;
                }

                if (is_array($handler)) {
                    [$target, $action] = $handler;
                    $controller = is_string($target) ? new $target() : $target;
                    $controller->$action($request, ...array_values($params));
                    return;
                }
            }
        }

        JsonResponse::error('NOT_FOUND', "Endpoint [{$method} {$path}] không tồn tại.", 404);
    }
}
