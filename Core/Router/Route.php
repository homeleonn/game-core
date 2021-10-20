<?php

namespace Core\Router;

use Closure;
use ReflectionMethod;
use ReflectionFunction;

class Route
{
    private static array $globalPatterns = [];

    private string $name;
    private array $middleware = [];
    private array $patterns;
    private array|null $actualArguments = [];

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

    public function getUri()
    {
        return $this->uri;
    }

    public function getMethod()
    {
        return $this->method;
    }

    public function getAction()
    {
        return $this->action;
    }

    public function getActualArguments()
    {
        return $this->actualArguments;
    }

    public function getResolveAction()
    {
        $this->prepareArguments();

        return function () {
            return call_user_func_array($this->action, $this->actualArguments);
        };
    }

    private function prepareArguments()
    {
        if ($this->action instanceof Closure) {
            $refMethod = new ReflectionFunction($this->action);
        } else {
            $refMethod = new ReflectionMethod($this->action[0], $this->action[1]);
        }
        $requiredArgs = $refMethod->getParameters();

        // if (count($this->actualArguments) < count($requiredArgs)) {
        //     $neededExtraArgsCount = count($requiredArgs) - count($this->actualArguments);
        //     $args = [];
        //     for ($i = 0; $i < $neededExtraArgsCount; $i++) {
        //         $className = $refMethod->getParameters()[0]->getClass()?->name;
        //         // dd($className, class_exists($className));
        //         if (!class_exists($className)) continue;
        //         try {
        //             $args[] = \App::make($className);
        //         } catch (\Exception $e) {
        //             $args[] = new $className(array_unshift($this->actualArguments));
        //         }
        //         echo 1;
        //     }
        //     $this->actualArguments = array_merge($args, $this->actualArguments);
        // }
        foreach ($requiredArgs as $idx => $param) {
            $className = $param->getType()?->getName();
            if (!class_exists($className)) continue;
            try {
                array_splice($this->actualArguments, $idx, 0, [\App::make($className)]);
            } catch (\Exception $e) {
                array_splice($this->actualArguments, $idx, 1, [(new $className())->find($this->actualArguments[$idx])]);
            }
        }
    }

    public function setAction(Closure|array $action)
    {
        return $this->action = $action;
    }

    public function getMiddleware()
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
     * @return matched params | false
     */
    public function match($method, $uri): array|bool
    {
        if ($this->method != $method) return false;

        $patchedUri = $this->patchUri();
        // d($uri, $patchedUri);

        if (preg_match('~^' . $patchedUri . '/?$~', $uri, $this->actualArguments)) {//dd($this->actualArguments);
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
