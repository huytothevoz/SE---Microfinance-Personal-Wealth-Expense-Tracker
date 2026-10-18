<?php

declare(strict_types=1);

namespace WealthFlow\Core;

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
        if ($request->getMethod() === 'OPTIONS') {
            header('Allow: GET, POST, PUT, DELETE');
            JsonResponse::error('METHOD_NOT_ALLOWED', 'Phương thức không được hỗ trợ.', 405);
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

        $matchedMethod = false;

        foreach ($this->routes as $route) {
            if (preg_match($route['pattern'], $cleanPath, $matches)) {
                if ($route['method'] === $method) {
                    $handler = $route['handler'];

                    // Filter named params only
                    $params = array_filter(
                        $matches,
                        static fn($key) => is_string($key),
                        ARRAY_FILTER_USE_KEY
                    );

                    if ($handler instanceof Closure) {
                        $handler($request, ...$params);
                    } elseif (is_array($handler)) {
                        [$controller, $action] = $handler;
                        $controller->$action($request, ...$params);
                    }
                    return;
                }
                $matchedMethod = true;
            }
        }

        if ($matchedMethod) {
            throw new ApiException('METHOD_NOT_ALLOWED', 'Phương thức HTTP không được hỗ trợ cho tài nguyên này.', 405);
        }

        throw new ApiException('ROUTE_NOT_FOUND', 'Đường dẫn API không tồn tại: ' . $cleanPath, 404);
    }
}
