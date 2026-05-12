<?php declare(strict_types=1);

namespace App\Routing;

use App\Controllers\NotFoundController;

/**
 * Registers HTTP routes and dispatches the current request to its handler.
 */
class Router
{
    private array $routes = [];

    /**
     * Register a GET route.
     *
     * @param string $path URI path pattern.
     * @param callable|array $handler Callable handler or [class, method] pair.
     * @return Route
     */
    public function get(string $path, callable|array $handler): Route
    {
        return $this->addRoute('GET', $path, $handler);
    }

    /**
     * Register a POST route.
     *
     * @param string $path URI path pattern.
     * @param callable|array $handler Callable handler or [class, method] pair.
     * @return Route
     */
    public function post(string $path, callable|array $handler): Route
    {
        return $this->addRoute('POST', $path, $handler);
    }

    /**
     * Register a PUT route.
     *
     * @param string $path URI path pattern.
     * @param callable|array $handler Callable handler or [class, method] pair.
     * @return Route
     */
    public function put(string $path, callable|array $handler): Route
    {
        return $this->addRoute('PUT', $path, $handler);
    }

    /**
     * Register a PATCH route.
     *
     * @param string $path URI path pattern.
     * @param callable|array $handler Callable handler or [class, method] pair.
     * @return Route
     */
    public function patch(string $path, callable|array $handler): Route
    {
        return $this->addRoute('PATCH', $path, $handler);
    }

    /**
     * Register a DELETE route.
     *
     * @param string $path URI path pattern.
     * @param callable|array $handler Callable handler or [class, method] pair.
     * @return Route
     */
    public function delete(string $path, callable|array $handler): Route
    {
        return $this->addRoute('DELETE', $path, $handler);
    }

    /**
     * Resolve the current HTTP request and execute the matching route.
     *
     * @return void
     */
    public function dispatch(): void
    {
        [$route, $params] = $this->resolve($_SERVER['REQUEST_METHOD'] ?? 'GET', $_SERVER['REQUEST_URI'] ?? '/');

        if ($route === null) {
            call_user_func([NotFoundController::class, 'index']);
            return;
        }

        $this->execute($route, $params);
    }

    private function resolve(string $method, string $uri): array
    {
        foreach ($this->routes as $route) {
            if ($route->matches($method, $uri)) {
                return [$route, $route->extractParameters($uri)];
            }
        }

        return [null, []];
    }

    private function execute(Route $route, array $params): void
    {
        foreach ($route->middlewares() as $middleware) {
            call_user_func([$middleware, 'handle'], $params);
        }

        call_user_func($route->handler(), $params);
    }

    private function addRoute(string $method, string $path, callable|array $handler): Route
    {
        $route = new Route([$method], $this->normalizePath($path), $handler);
        $this->routes[] = $route;

        return $route;
    }

    private function normalizePath(string $path): string
    {
        if ($path !== '/') {
            $path = rtrim($path, '/');
        }

        return $path ?: '/';
    }
}
