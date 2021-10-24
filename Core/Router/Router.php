<?php

namespace Core\Router;

use Closure;
use Exception;
use Core\Support\Str;
use Core\Http\Request;
use Core\Http\Response;

class Router
{
    private Request $request;
    private Response $response;
    private array $routes = [];
    private $lastRoute;
    private $groupOptions = [];
    private $routeMiddleware = [
        'auth' => \App\Middleware\AuthMiddleware::class,
        'guest' => \App\Middleware\GuestMiddleware::class,
    ];

    public function __construct(Request $request, Response $response)
    {
        $this->request = $request;
        $this->response = $response;
    }

    public function get(string $uri,  $callback)
    {
        $this->addRoute('get', $uri, $callback);

        return $this;
    }

    public function post(string $uri, $callback)
    {
        $this->addRoute('post', $uri, $callback);

        return $this;
    }

    private function addRoute(string $method, string $uri, $callback)
    {
        $uri = Str::addStartSlash($uri);
        $route = new Route($method, $uri, $callback);
        $this->routes[] = $route;

        if (empty($this->groupOptions)) return;

        foreach ($this->groupOptions as $option => $value) {
            $route->{$option}($value);
        }

        $this->lastRoute = $route;
    }

    public function group(array $options, Closure $register)
    {
        $this->groupOptions = $options;
        $register();
        $this->groupOptions = [];
    }

    public function name(string $name)
    {
        array_pop($this->routes);
        $this->routes[$name] = $this->lastRoute;

        return $this;
    }

    public function getByName($name, $part = null)
    {
        if (!isset($this->routes[$name])) {
            throw new Exception("Named route {$name} not found");
        }

        return $this->routes[$name];
    }

    public function middleware(string $middleware)
    {
        $this->lastRoute->middleware($middleware);

        return $this;
    }

    private function findRoute()
    {
        $method     = strtolower($this->request->getMethod());
        $uri        = $this->request->getUri();
        $route      = null;
        $notFound   = $action = $isClosure = false;

        foreach ($this->routes as $_route) {
            if (!$_route->match($method, $uri))  continue;
            $route = $_route;
            break;
        }

        if (!$route) {
            $notFound = true;
        } else {
            $action = $route->getAction();
            if ($action instanceof Closure) {
                $isClosure = true;

                if (!is_callable($action)) {
                    $notFound = true;
                }
            } elseif (!call_user_func_array('method_exists', $action)) {
                $notFound = true;
            }
        }

        if ($notFound) {
            $this->response->setStatusCode(404);
            try {
                exit(view('404'));
            } catch (\Exception $e) {
                throw new HttpNotFoundException('Page not found');
            }
        }

        if (!$isClosure) {
            [$controllerClassName, $method] = $action;
            $controller = new $controllerClassName;
            $route->setAction([$controller, $method]);
        }

        return $route;
    }

    public function resolve()
    {
        $route = $this->findRoute();
        // dd($this->request->routeResolveAction);
        $this->request->routeResolveAction = $route->getResolveAction();
        $activatedMiddlewareStack = $this->activationMiddlewareStack($route->getMiddleware());

        return $activatedMiddlewareStack($this->request);
    }

    private function activationMiddlewareStack($middleware)
    {
        return array_reduce(
            array_reverse($middleware),
            $this->carry(),
            $this->response->fire()
        );
    }

    public function carry()
    {
        return function ($stack, $pipe) {
            return function ($passable) use ($stack, $pipe) {
                if ($pipe instanceof Closure) {
                    return $pipe($passable, $stack);
                }

                if (!class_exists($pipe) && !$pipe = $this->checkMiddleware($pipe)) {
                    throw new Exception("Middleware {$pipe} not found");
                }

                return (new $pipe)->handle($passable, $stack);
            };
        };
    }

    public function checkMiddleware(string $middleware)
    {
        if (isset($this->routeMiddleware[$middleware]) &&
            class_exists($this->routeMiddleware[$middleware])) {
            return $this->routeMiddleware[$middleware];
        }

        throw new Exception("Middleware {$middleware} not found");
    }
}
