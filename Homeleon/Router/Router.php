<?php

namespace Homeleon\Router;

use App;
use Closure;
use Exception;
use Homeleon\Support\Str;
use Homeleon\Http\Request;
use Homeleon\Http\Response;

class Router
{
    private Request $request;
    private Response $response;
    private array $routes = [];
    private $lastRoute;
    private $groupOptions = [];
    private $routeMiddleware = [
        'auth' => [\App\Middleware\AuthMiddleware::class],
        'guest' => [\App\Middleware\GuestMiddleware::class],
    ];

    public function setMiddlewareGroups(array $middlewareGroups): void
    {
        foreach ($middlewareGroups as $group => $middlewares) {
            $this->routeMiddleware[$group] = $middlewares;
        }
    }

    public function getMiddlewareGroup(string $group): array
    {
        return $this->routeMiddleware[$group] ?? [];
    }

    public function __construct(Request $request, Response $response)
    {
        $this->request = $request;
        $this->response = $response;
    }

    public function get(string $uri,  $callback): self
    {
        return $this->addRoute('get', $uri, $callback);
    }

    public function post(string $uri, $callback): self
    {
        return $this->addRoute('post', $uri, $callback);
    }

    public function options(string $uri, $callback): self
    {
        return $this->addRoute('options', $uri, $callback);
    }

    private function addRoute(string $method, string $uri, $callback): self
    {
        $uri = Str::addStartSlash($uri);
        $patchingRoutes = [];

        if (isset($this->groupOptions['middleware']) && $this->groupOptions['middleware'] == 'api') {
            $optionRoute = new Route('options', $uri, function(){});
            $this->routes[] = $optionRoute;
            $patchingRoutes[] = $optionRoute;
        }

        $route = new Route($method, $uri, $callback);
        $this->routes[] = $route;
        $patchingRoutes[] = $route;

        foreach ($this->groupOptions as $option => $value) {
            array_map(function ($route) use ($option, $value) {
                $route->{$option}($value);
            }, $patchingRoutes);
        }

        $this->lastRoute = $route;

        return $this;
    }

    public function prefix(string $prefix): self
    {
        $this->groupOptions['prefix'] = $prefix;

        return $this;
    }

    public function group(string|array|Closure $options, Closure $register = null): void
    {
        if ($options instanceof Closure) {
            $register = $options;
            $options = [];
        } elseif (is_string($options)) {
            if (!file_exists($options)) {
                throw new Exception('Route file not found: ' . $options);
            }

            $register = function () use ($options) {
                require $options;
            };
            $options = [];
        }

        $this->groupOptions = array_merge($this->groupOptions, $options);
        $register();
        $this->groupOptions = [];
    }

    public function name(string $name): self
    {
        array_pop($this->routes);
        $this->routes[$name] = $this->lastRoute;

        return $this;
    }

    public function getByName($name, $part = null): Route
    {
        if (!isset($this->routes[$name])) {
            throw new Exception("Named route {$name} not found");
        }

        return $this->routes[$name];
    }

    public function middleware(string $middleware): self
    {
        $this->groupOptions['middleware'] = $middleware;

        return $this;
    }

    private function findRoute(): Route
    {
        $method     = strtolower($this->request->getMethod());
        $uri        = $this->request->getUri();
        $route      = null;
        $notFound   = $isClosure = false;
        $action     = [];

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

            $controller = App::prepareObject($controllerClassName);
            // $controller = new $controllerClassName;
            $route->setAction([$controller, $method]);
        }

        return $route;
    }

    public function resolve(): mixed
    {
        $route = $this->findRoute();
        $this->request->routeResolveAction = $route->getResolveAction();
        $activatedMiddlewareStack = $this->activationMiddlewareStack($route->getMiddleware());

        return $activatedMiddlewareStack($this->request);
    }

    private function activationMiddlewareStack($middleware): mixed
    {
        return array_reduce(
            array_reverse($middleware),
            $this->carry(),
            $this->response->fire()
        );
    }

    public function carry(): Closure
    {
        return function ($stack, $pipe) {
            return function ($passable) use ($stack, $pipe) {
                if ($pipe instanceof Closure) {
                    return $pipe($passable, $stack);
                }

//                if (!class_exists($pipe) && !$pipe = $this->checkMiddleware($pipe)) {
//                    throw new Exception("Middleware {$pipe} not found");
//                }

                $res = (new $pipe)->handle($passable, $stack);

                return $res;
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

    public static function pattern(string $param, string $pattern): void
    {
        Route::$globalPatterns[$param] = $pattern;
    }
}

