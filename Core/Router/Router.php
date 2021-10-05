<?php

namespace Core\Router;

use Closure;
use Exception;
use Core\Http\Request;
use Core\Http\Response;


class Router
{
    private Request $request;
    private Response $response;
    private array $routes = [];
    private array $byName = [];
    private array $groups = [];
    private $lastRoute;
    private $lastRouteUri;
    // private $dispatchedRoute;
    private $currentRouteOptions = [];
    private $currentOptions = [];
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
        $uri = \prepareUri($uri);
        $this->currentOptions['callback'] = $callback;

        foreach ($this->currentOptions as $option => $value) {
            $this->routes[$method][$uri][$option] = $value;
        }

        $this->lastRoute = &$this->routes[$method][$uri];
        $this->lastRouteUri = $uri;
    }

    public function group(array $options, Closure $routeList)
    {
        $this->currentOptions = $options;
        $routeList();
        $this->currentOptions = [];
    }

    public function name(string $name)
    {
        $this->byName[$name] = [
            'route' => $this->lastRoute,
            'uri' => $this->lastRouteUri,
        ];
    }

    public function getByName($name, $part = null)
    {
        if (!isset($this->byName[$name])) {
            throw new Exception("Named route {$name} not found");
        }

        return ($part && isset($this->byName[$name][$part])) ? $this->byName[$name][$part] : $this->byName[$name];
    }

    public function middleware(string $middleware)
    {
        $this->lastRoute['middleware'][] = $middleware;
    }

    public function getRouteResolver($method, $uri)
    {
        if (isset($this->routes[$method][$uri])) {
            return $this->routes[$method][$uri]['callback'];
        }

        throw new Exception("Route resolver not found with method {$method} and path {$uri}");
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

    public function preparedDestination()
    {
        return function ($request, $next) {
            return $next($request);
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

    public function resolve()
    {
        // dd($this->routes);
        $method     = strtolower($this->request->getMethod());
        $uri       = $this->request->getUri();
        $notFound   = $callback = $isClosure = false;

        if (!isset($this->routes[$method][$uri])) {
            $notFound = true;
        } else{
            $callback = $this->routes[$method][$uri]['callback'];
            
            if ($callback instanceof Closure) {
                $isClosure = true;

                if (!is_callable($callback)) {
                    $notFound = true;
                }
            } elseif (!call_user_func_array('method_exists', $callback)) {
                $notFound = true;
            }
        }

        if ($notFound) {
            $this->response->setStatusCode(404);
            return 'Page not found';
        }

        if (!$isClosure) {
            list($class, $runMethod) = $callback;
            $class = new $class;
            $callback = [$class, $runMethod];
        }

        $this->request->routeResolver = $callback;

        $middleware = [];
        if (isset($this->routes[$method][$uri]['middleware'])) {
            $middleware = is_array($this->routes[$method][$uri]['middleware'])  ? 
                                $this->routes[$method][$uri]['middleware'] : 
                                [$this->routes[$method][$uri]['middleware']];
        }
        
        $middleware[] = $this->preparedDestination();
        $middleware = array_reduce(
                array_reverse($middleware),
                $this->carry(), 
                $this->response
            );

        return $middleware($this->request);

        // return call_user_func($callback);
    }
}