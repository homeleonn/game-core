<?php

namespace Homeleon\Router;

use Closure;
use ReflectionMethod;
use ReflectionFunction;
use ReflectionNamedType;
use Homeleon\Support\Facades\App;
use Homeleon\Support\Facades\Response;

class Route
{
    private static array $globalPatterns = [];

    private string $name;
    private array $middleware = [];
    private array $patterns;
    private ?array $actualArguments = [];

    public function __construct(
        private string $method,
        private string $uri,
        private Closure|array $action,
    ) {
        $this->patterns = self::$globalPatterns;
    }

    public function name(string $name): void
    {
        $this->name = $name;
    }

    public function getUri(): string
    {
        return $this->uri;
    }

    public function buildUri(?array $params = null): mixed
    {
        return preg_replace_callback('~{\w+\??}~', function ($placeholder) use ($params) {
            static $i = 0;
            return $params[$i++] ?? $placeholder[0];
        }, $this->uri);
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getAction()
    {
        return $this->action;
    }

    public function getActualArguments(): ?array
    {
        return $this->actualArguments;
    }

    public function getResolveAction(): Closure
    {
        $this->prepareArguments();

        return function () {
            return call_user_func_array($this->action, $this->actualArguments);
        };
    }

    private function prepareArguments(): void
    {
        if ($this->action instanceof Closure) {
            $refMethod = new ReflectionFunction($this->action);
        } else {
            $refMethod = new ReflectionMethod($this->action[0], $this->action[1]);
        }

        $requiredArgs = $refMethod->getParameters();

        foreach ($requiredArgs as $idx => $param) {
            $classType = $param->getType();

            if ($classType instanceof ReflectionNamedType) {
                $className = $classType->getName();
            }

            if (!isset($className) || !class_exists($className)) continue;

            try {
                array_splice($this->actualArguments, $idx, 0, [App::make($className)]);
            } catch (\Exception $e) {
                if (!isset($this->actualArguments[$idx])) continue;

                $model = (new $className())->find($this->actualArguments[$idx]);

                if (!$model) {
                    Response::setStatusCode(404);
                    try {
                        exit(view('404'));
                    } catch (\Exception $e) {
                        throw new HttpNotFoundException('Page not found');
                    }
                }

                array_splice($this->actualArguments, $idx, 1, [$model]);
            }
        }
    }

    public function setAction(Closure|array $action)
    {
        return $this->action = $action;
    }

    public function getMiddleware(): array
    {
        return $this->middleware;
    }

    /**
     * Set route middleware
     */
    public function middleware(string|array $middleware): void
    {
        if (is_string($middleware)) {
            $this->middleware[] = $middleware;
        } else {
            $this->middleware = array_merge($this->middleware, $middleware);
        }
    }

    public function where(array $patterns): void
    {
        $this->patterns = array_merge($this->patterns, $patterns);
    }

    /**
     * Search match with actual uri by regex
     *
     * @return bool matched params | false
     */
    public function match($method, $uri): array|bool
    {
        if ($this->method != $method) return false;

        $patchedUri = $this->patchUri();

        if (preg_match('~^' . $patchedUri . '/?$~', $uri, $this->actualArguments)) {
            array_shift($this->actualArguments);
            return true;
        }

        return false;
    }

    /**
     * Replace named uri params by regex alternative
     *
     * @return string patched uri
     */
    private function patchUri(): string
    {
        $patternedUri = preg_replace_callback(
            '~/({(?P<param>\w+)(?P<required>\??)})~',
            fn($matches) => '/?(' . ($this->patterns[$matches['param']] ?? '\w+') . ')' . $matches['required'],
          $this->uri
        );

        return rtrim($patternedUri, '/');
    }

    public static function pattern(string $param, string $pattern): void
    {
        self::$globalPatterns[$param] = $pattern;
    }
}
