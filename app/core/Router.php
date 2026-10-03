<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Minimal HTTP router. Supports static paths and {param} placeholders.
 */
final class Router
{
    /** @var array<string, array<int, array{pattern:string, params:string[], handler:array|callable}>> */
    private array $routes = ['GET' => [], 'POST' => []];

    /** @param array{0:class-string,1:string}|callable $handler */
    public function get(string $path, array|callable $handler): void
    {
        $this->add('GET', $path, $handler);
    }

    /** @param array{0:class-string,1:string}|callable $handler */
    public function post(string $path, array|callable $handler): void
    {
        $this->add('POST', $path, $handler);
    }

    /** @param array{0:class-string,1:string}|callable $handler */
    private function add(string $method, string $path, array|callable $handler): void
    {
        $params = [];
        $pattern = preg_replace_callback('#\{([a-zA-Z_][a-zA-Z0-9_]*)\}#', function ($m) use (&$params) {
            $params[] = $m[1];
            return '([^/]+)';
        }, $path);
        $this->routes[$method][] = [
            'pattern' => '#^' . $pattern . '$#',
            'params' => $params,
            'handler' => $handler,
        ];
    }

    public function dispatch(string $method, string $uri): void
    {
        $method = strtoupper($method);
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';
        $path = '/' . trim($path, '/');
        if ($path === '/') {
            $path = '/';
        }

        $candidates = $this->routes[$method] ?? [];
        foreach ($candidates as $route) {
            if (preg_match($route['pattern'], $path, $matches)) {
                array_shift($matches);
                $args = [];
                foreach ($route['params'] as $i => $name) {
                    $args[$name] = $matches[$i] ?? null;
                }
                $this->invoke($route['handler'], array_values($args));
                return;
            }
        }

        http_response_code(404);
        echo View::render('errors/404', ['title' => 'Not Found'], 'layouts/guest');
    }

    /**
     * Invoke a route handler, instantiating the controller for [Class, method].
     *
     * @param array{0:class-string,1:string}|callable $handler
     */
    private function invoke(array|callable $handler, array $args): void
    {
        if (is_array($handler)) {
            [$class, $method] = $handler;
            $controller = new $class();
            $controller->{$method}(...$args);
            return;
        }
        $handler(...$args);
    }
}