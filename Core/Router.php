<?php


namespace App\Core;


class Router
{
    private Request $request;
    private Response $response;
    private array $routes = [];

    public function __construct(Request $request, Response $response)
    {
        $this->request = $request;
        $this->response = $response;
    }

    public function get(string $path,  $callback): void
    {
        $this->addRoute('get', $path, $callback);
    }

    public function post(string $path, $callback): void
    {
        $this->addRoute('post', $path, $callback);
    }

    private function addRoute(string $type, string $path, $callback)
    {
        $this->routes[$type][trim($path, '/')] = $callback;
    }

    public function resolve()
    {
        $method = strtolower($this->request->getMethod());
        $path   = $this->request->getPath();

        if (!isset($this->routes[$method][$path]) || !is_callable($this->routes[$method][$path])) {
            $this->response->getStatusCode(404);
            return 'Page not found';
        }

        return call_user_func($this->routes[$method][$path]);
    }
}