<?php declare(strict_types=1);

namespace App\Routing;

/**
 * Represents one route definition, including path parameters and middleware.
 */
final class Route
{
    private array $methods;
    private string $path;
    private mixed $handler;

    private array $middlewares = [];
    private ?string $name = null;
    private array $constraints = [];
    private ?string $compiledRegex = null;
    private array $parameterNames = [];

    /**
     * Create a route definition.
     *
     * @param array $methods HTTP methods accepted by the route.
     * @param string $path URI path pattern, including optional `{parameter}` tokens.
     * @param callable|array $handler Callable handler or [class, method] pair.
     * @throws \InvalidArgumentException When methods or path are invalid.
     */
    public function __construct(array $methods, string $path, callable|array $handler)
    {
        $this->methods = $this->normalizeMethods($methods);
        $this->path = $this->normalizePath($path);
        $this->handler = $handler;
    }

    /**
     * Return the normalized HTTP methods accepted by the route.
     *
     * @return array
     */
    public function methods(): array
    {
        return $this->methods;
    }

    /**
     * Return the normalized route path.
     *
     * @return string
     */
    public function path(): string
    {
        return $this->path;
    }

    /**
     * Return the route handler.
     *
     * @return callable|array
     */
    public function handler(): callable|array
    {
        return $this->handler;
    }

    /**
     * Return middleware class names attached to the route.
     *
     * @return array
     */
    public function middlewares(): array
    {
        return $this->middlewares;
    }

    /**
     * Return the optional route name.
     *
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * Return the parameter names found while compiling the route.
     *
     * @return array
     */
    public function parameterNames(): array
    {
        return $this->parameterNames;
    }

    /**
     * Attach one or more middleware classes to the route.
     *
     * @param array|string $middleware Middleware class name or list of class names.
     * @return self
     */
    public function middleware(array|string $middleware): self
    {
        $items = is_array($middleware) ? $middleware : [$middleware];

        foreach ($items as $item) {
            $item = trim($item);

            if ($item === '' || in_array($item, $this->middlewares, true)) {
                continue;
            }

            $this->middlewares[] = $item;
        }

        return $this;
    }

    /**
     * Assign a name to the route for later reference.
     *
     * @param string $name Route name.
     * @return self
     * @throws \InvalidArgumentException When the name is empty.
     */
    public function name(string $name): self
    {
        $name = trim($name);

        if ($name === '') {
            throw new \InvalidArgumentException('Route name cannot be empty.');
        }

        $this->name = $name;
        return $this;
    }

    /**
     * Add regex constraints for path parameters.
     *
     * @param string|array $parameter Parameter name or associative array of constraints.
     * @param string|null $pattern Regex pattern used when `$parameter` is a string.
     * @return self
     * @throws \InvalidArgumentException When a single parameter constraint is incomplete.
     */
    public function where(string|array $parameter, ?string $pattern = null): self
    {
        if (is_array($parameter)) {
            foreach ($parameter as $key => $value) {
                $key = trim((string) $key);
                $value = trim((string) $value);

                if ($key === '' || $value === '') {
                    continue;
                }

                $this->constraints[$key] = $value;
            }
        } else {
            $parameter = trim($parameter);
            $pattern = trim((string) $pattern);

            if ($parameter === '' || $pattern === '') {
                throw new \InvalidArgumentException('Constraint parameter and pattern cannot be empty.');
            }

            $this->constraints[$parameter] = $pattern;
        }

        $this->compiledRegex = null;
        $this->parameterNames = [];

        return $this;
    }

    /**
     * Check whether the route accepts an HTTP method.
     *
     * @param string $method HTTP method to test.
     * @return bool
     */
    public function allowMethod(string $method): bool
    {
        return in_array(strtoupper(trim($method)), $this->methods, true);
    }

    /**
     * Check whether a method and URI match this route.
     *
     * @param string $method HTTP method to test.
     * @param string $path URI or path to test.
     * @return bool
     * @throws \RuntimeException When the compiled route regex cannot be evaluated.
     */
    public function matches(string $method, string $path): bool
    {
        return $this->allowMethod($method) && $this->matchesPath($path);
    }

    /**
     * Extract named parameters from a URI that matches this route.
     *
     * @param string $uri Request URI.
     * @return array
     * @throws \RuntimeException When parameter extraction fails.
     */
    public function extractParameters(string $uri): array
    {
        $regex = $this->compile();
        $normalizedUri = $this->normalizeUri($uri);
        $result = preg_match($regex, $normalizedUri, $matches);

        if ($result === false) {
            throw new \RuntimeException("Failed to extract parameters for route [{$this->path}].");
        }

        if ($result !== 1) {
            return [];
        }

        $parameters = [];

        foreach ($this->parameterNames as $name) {
            if (array_key_exists($name, $matches)) {
                $parameters[$name] = (string) $matches[$name];
            }
        }

        return $parameters;
    }

    /**
     * Build a URL by replacing route parameters with encoded values.
     *
     * @param array $params Values keyed by route parameter name.
     * @return string
     * @throws \InvalidArgumentException When a required route parameter is missing.
     * @throws \RuntimeException When URL generation fails.
     */
    public function buildUrl(array $params = []): string
    {
        $url = preg_replace_callback(
            '/\{([a-zA-Z_][a-zA-Z0-9_-]*)\}/',
            function (array $matches) use ($params): string {
                $parameter = $matches[1];

                if (!array_key_exists($parameter, $params)) {
                    throw new \InvalidArgumentException("Missing route parameter [{$parameter}] for route [{$this->path}].");
                }

                return rawurlencode((string) $params[$parameter]);
            },
            $this->path
        );

        if ($url === null) {
            throw new \RuntimeException("Failed to build URL for route [{$this->path}].");
        }

        return $url;
    }

    private function matchesPath(string $uri): bool
    {
        $regex = $this->compile();
        $normalizedUri = $this->normalizeUri($uri);
        $result = preg_match($regex, $normalizedUri);

        if ($result === false) {
            throw new \RuntimeException("Failed to evaluate route with regex for path [{$this->path}].");
        }

        return $result === 1;
    }

    private function compile(): string
    {
        if ($this->compiledRegex !== null) {
            return $this->compiledRegex;
        }

        $this->parameterNames = [];
        $pattern = preg_replace_callback(
            '/\{([a-zA-Z_][a-zA-Z0-9_-]*)\}/',
            function (array $matches): string {
                $parameter = $matches[1];
                $this->parameterNames[] = $parameter;
                $constraint = $this->constraints[$parameter] ?? '[^/]+';

                return '(?P<' . $parameter . '>' . $constraint . ')';
            },
            $this->path
        );

        if ($pattern === null) {
            throw new \RuntimeException("Failed to compile route pattern [{$this->path}].");
        }

        $this->compiledRegex = '#^' . $pattern . '$#';
        return $this->compiledRegex;
    }

    private function normalizeMethods(array $methods): array
    {
        if ($methods === []) {
            throw new \InvalidArgumentException('A route must define at least one HTTP method.');
        }

        $normalized = [];

        foreach ($methods as $method) {
            $method = strtoupper(trim((string) $method));

            if ($method === '') {
                continue;
            }

            if (!in_array($method, ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'], true)) {
                throw new \InvalidArgumentException("Unsupported HTTP method [{$method}].");
            }

            if (!in_array($method, $normalized, true)) {
                $normalized[] = $method;
            }
        }

        if ($normalized === []) {
            throw new \InvalidArgumentException('A route must define at least one HTTP method.');
        }

        return $normalized;
    }

    private function normalizePath(string $path): string
    {
        $path = trim($path);

        if ($path === '') {
            throw new \InvalidArgumentException('Route path cannot be empty.');
        }

        if ($path[0] !== '/') {
            $path = '/' . $path;
        }

        if ($path !== '/' && str_ends_with($path, '/')) {
            $path = rtrim($path, '/');
        }

        return $path;
    }

    private function normalizeUri(string $uri): string
    {
        $path = parse_url($uri, PHP_URL_PATH);

        if (!is_string($path) || $path === '') {
            $path = '/';
        }

        if ($path !== '/' && str_ends_with($path, '/')) {
            $path = rtrim($path, '/');
        }

        return $path;
    }
}
