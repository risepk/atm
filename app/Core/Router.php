<?php
// app/Core/Router.php

class Router {
    private array $routes = [];
    private array $namedMiddlewares = [];

    public function registerMiddleware(string $name, string $className): void {
        $this->namedMiddlewares[$name] = $className;
    }

    public function get(string $path, $handler, array $middlewares = []): void {
        $this->addRoute('GET', $path, $handler, $middlewares);
    }

    public function post(string $path, $handler, array $middlewares = []): void {
        $this->addRoute('POST', $path, $handler, $middlewares);
    }

    public function put(string $path, $handler, array $middlewares = []): void {
        $this->addRoute('PUT', $path, $handler, $middlewares);
    }

    public function delete(string $path, $handler, array $middlewares = []): void {
        $this->addRoute('DELETE', $path, $handler, $middlewares);
    }

    private function addRoute(string $method, string $path, $handler, array $middlewares): void {
        $path = '/' . trim($path, '/');
        if ($path === '') $path = '/';
        
        $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<$1>[^/]+)', $path);
        $regex = '#^' . $pattern . '$#';

        $this->routes[] = [
            'method' => $method,
            'path' => $path,
            'regex' => $regex,
            'handler' => $handler,
            'middlewares' => $middlewares
        ];
    }

    public function dispatch(Request $request): void {
        $method = $request->getMethod();
        $uri = $request->getUri();

        // Support .php extensions transparently (e.g. /dashboard.php -> /dashboard)
        // This allows user to visit both with and without .php without breaking anything.
        $normalizedUri = preg_replace('/\.php$/i', '', $uri);
        if ($normalizedUri === '') {
            $normalizedUri = '/';
        }

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            if (preg_match($route['regex'], $uri, $matches) || preg_match($route['regex'], $normalizedUri, $matches)) {
                $params = [];
                foreach ($matches as $k => $v) {
                    if (is_string($k)) {
                        $params[$k] = $v;
                    }
                }

                // Execute middleware pipeline
                $this->runMiddlewarePipeline($route['middlewares'], $request, function() use ($route, $request, $params) {
                    $this->executeHandler($route['handler'], $request, $params);
                });
                return;
            }
        }

        // 404 handler
        if ($request->isAjax()) {
            Response::error("Resource not found: {$uri}", null, 404);
        } else {
            http_response_code(404);
            Response::render('errors/404', ['uri' => $uri]);
        }
    }

    /**
     * Helper to inspect route matching for automated tests and route resolution verification.
     */
    public function matchRoute(string $method, string $uri): ?array {
        $method = strtoupper($method);
        $normalizedUri = preg_replace('/\.php$/i', '', $uri);
        if ($normalizedUri === '') {
            $normalizedUri = '/';
        }

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }
            if (preg_match($route['regex'], $uri, $matches) || preg_match($route['regex'], $normalizedUri, $matches)) {
                $params = [];
                foreach ($matches as $k => $v) {
                    if (is_string($k)) {
                        $params[$k] = $v;
                    }
                }
                return [
                    'route' => $route,
                    'params' => $params
                ];
            }
        }
        return null;
    }

    private function runMiddlewarePipeline(array $middlewareNames, Request $request, Closure $destination): void {
        $pipeline = array_reverse($middlewareNames);
        $next = $destination;

        foreach ($pipeline as $name) {
            $class = $this->namedMiddlewares[$name] ?? $name;
            if (!class_exists($class)) {
                throw new Exception("Middleware class [{$class}] not found.");
            }
            $instance = new $class();
            $prevNext = $next;
            $next = function() use ($instance, $request, $prevNext) {
                return $instance->handle($request, $prevNext);
            };
        }

        $next();
    }

    private function executeHandler($handler, Request $request, array $params): void {
        if (is_callable($handler)) {
            call_user_func($handler, $request, $params);
            return;
        }

        if (is_string($handler) && strpos($handler, '@') !== false) {
            [$controllerName, $action] = explode('@', $handler);
            if (!class_exists($controllerName)) {
                throw new Exception("Controller [{$controllerName}] not found.");
            }
            $controller = new $controllerName();
            if (!method_exists($controller, $action)) {
                throw new Exception("Action [{$action}] not found on controller [{$controllerName}].");
            }
            $controller->$action($request, $params);
            return;
        }

        throw new Exception("Invalid route handler defined.");
    }
}
