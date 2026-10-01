<?php

class Router
{
    private array $routes = [];

    private function add(string $method, string $pattern, callable $handler): void
    {
        $regex = preg_replace_callback('/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/', static function ($match) {
            return '(?P<' . $match[1] . '>[^/]+)';
        }, $pattern);
        $this->routes[] = [$method, '#^' . $regex . '/?$#', $handler];
    }

    public function get(string $path, callable $handler): void { $this->add('GET', $path, $handler); }
    public function post(string $path, callable $handler): void { $this->add('POST', $path, $handler); }
    public function put(string $path, callable $handler): void { $this->add('PUT', $path, $handler); }
    public function delete(string $path, callable $handler): void { $this->add('DELETE', $path, $handler); }

    public function dispatch(string $method, string $path): void
    {
        foreach ($this->routes as [$routeMethod, $regex, $handler]) {
            if ($routeMethod === $method && preg_match($regex, $path, $matches)) {
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                $handler(...array_values($params));
                return;
            }
        }
        Response::error('ไม่พบ API endpoint นี้', 404);
    }
}
