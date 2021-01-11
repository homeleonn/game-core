<?php


namespace App\Core;


class Router
{
    private Request $request;
    private array $routes = [];

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function get(string $path, callable $callback): void
    {
        $this->addRoute('get', $path, $callback);
    }

    public function post(string $path, callable $callback): void
    {
        $this->addRoute('post', $path, $callback);
    }

    private function addRoute(string $type, string $path, callable $callback)
    {
        $this->routes[$type][trim($path, '/')] = $callback;
    }

    public function resolve()
    {
        $method = strtolower($this->request->getMethod());
        $path   = $this->request->getPath();

        if (!isset($this->routes[$method][$path])) {
            http_response_code(404);
            exit('Page not found');
        }

        return call_user_func($this->routes[$method][$path]);
    }
}