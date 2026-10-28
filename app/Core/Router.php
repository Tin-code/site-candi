<?php

namespace App\Core;

class Router
{
    private array $routes     = [];
    private array $middlewares = [];
    private string $prefix    = '';
    private array $groupMiddlewares = [];

    // -------------------------------------------------------------------------
    // Enregistrement des routes
    // -------------------------------------------------------------------------

    public function get(string $path, array|callable $handler): self
    {
        return $this->addRoute('GET', $path, $handler);
    }

    public function post(string $path, array|callable $handler): self
    {
        return $this->addRoute('POST', $path, $handler);
    }

    public function put(string $path, array|callable $handler): self
    {
        return $this->addRoute('PUT', $path, $handler);
    }

    public function patch(string $path, array|callable $handler): self
    {
        return $this->addRoute('PATCH', $path, $handler);
    }

    public function delete(string $path, array|callable $handler): self
    {
        return $this->addRoute('DELETE', $path, $handler);
    }

    public function any(string $path, array|callable $handler): self
    {
        foreach (['GET', 'POST', 'PUT', 'PATCH', 'DELETE'] as $method) {
            $this->addRoute($method, $path, $handler);
        }
        return $this;
    }

    /** Groupe de routes avec préfixe et/ou middlewares communs. */
    public function group(string $prefix, callable $callback, array $middlewares = []): void
    {
        $previousPrefix      = $this->prefix;
        $previousMiddlewares = $this->groupMiddlewares;

        $this->prefix              = $previousPrefix . $prefix;
        $this->groupMiddlewares    = array_merge($previousMiddlewares, $middlewares);

        $callback($this);

        $this->prefix           = $previousPrefix;
        $this->groupMiddlewares = $previousMiddlewares;
    }

    private function addRoute(string $method, string $path, array|callable $handler): self
    {
        $fullPath = $this->prefix . $path;
        $this->routes[] = [
            'method'      => $method,
            'path'        => $fullPath,
            'pattern'     => $this->buildPattern($fullPath),
            'handler'     => $handler,
            'middlewares' => $this->groupMiddlewares,
        ];
        return $this;
    }

    // -------------------------------------------------------------------------
    // Résolution
    // -------------------------------------------------------------------------

    public function dispatch(Request $request): void
    {
        $method = $request->method();
        $path   = $request->path();

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            if (!preg_match($route['pattern'], $path, $matches)) {
                continue;
            }

            // Params dynamiques (:id, :slug …)
            $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
            $request->setParams($params);

            // Middlewares
            foreach ($route['middlewares'] as $mw) {
                $this->runMiddleware($mw, $request);
            }

            // Handler
            $this->runHandler($route['handler'], $request);
            return;
        }

        // 404
        http_response_code(404);
        View::make('errors/error', ['code' => 404, 'title' => 'Page introuvable', 'message' => '']);
    }

    // -------------------------------------------------------------------------
    // Exécution
    // -------------------------------------------------------------------------

    private function runHandler(array|callable $handler, Request $request): void
    {
        if (is_callable($handler)) {
            $handler($request);
            return;
        }

        [$controllerClass, $method] = $handler;
        if (!class_exists($controllerClass)) {
            throw new \RuntimeException("Contrôleur introuvable : {$controllerClass}");
        }
        $controller = new $controllerClass($request);
        $controller->$method($request);
    }

    private function runMiddleware(string|callable $middleware, Request $request): void
    {
        if (is_callable($middleware)) {
            $middleware($request);
            return;
        }

        // Middleware enregistré par alias
        if (isset($this->middlewares[$middleware])) {
            $fn = $this->middlewares[$middleware];
            $fn($request);
            return;
        }

        // Classe middleware
        if (class_exists($middleware)) {
            (new $middleware())->handle($request);
            return;
        }

        throw new \RuntimeException("Middleware introuvable : {$middleware}");
    }

    // -------------------------------------------------------------------------
    // Conversion du pattern de route en regex
    // -------------------------------------------------------------------------

    private function buildPattern(string $path): string
    {
        $pattern = preg_replace('/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/', '(?P<$1>[^/]+)', $path);
        return '#^' . $pattern . '$#';
    }

    // -------------------------------------------------------------------------
    // Alias de middlewares
    // -------------------------------------------------------------------------

    public function aliasMiddleware(string $name, callable $fn): void
    {
        $this->middlewares[$name] = $fn;
    }
}
