<?php

namespace Homeleon;

use Homeleon\Support\Facades\Facade;
use Homeleon\Support\Facades\Response;
use Closure;
use Exception;
use ReflectionClass;

class App
{
    protected array $container = [];
    private string $projectDir;

    public function __construct()
    {
        $this->setProjectDir();
        $this->coreAliasesRegister();
        $config = require  ROOT . '/config/app.php';
        Facade::setFacadeApplication($this, $config['aliases']);
        $servicesInstances = $this->loadServices($config['providers']);
        $this->bootServices($servicesInstances);
        $this->checkKey();
    }

    protected function loadServices(array $services): array
    {
        $servicesInstances = [];

        foreach ($services as $service) {
            $serviceInstance = new $service($this);
            $serviceInstance->register();
            $servicesInstances[] = $serviceInstance;
        }

        return $servicesInstances;
    }

    protected function bootServices($services)
    {
        foreach ($services as $service) {
            $service->boot();
        }
    }

    private function setProjectDir()
    {
        if (defined('ROOT')) return;

        $find = false;
        $maxDeep = 10;
        $dir = __DIR__;

        do {
            $dir = dirname($dir);
            if (file_exists($dir . '/composer.json')) {
                $find = true;
                break;
            }
            if (!$maxDeep--) break;
        } while (!$find);

        if ($find) {
            define('ROOT', $dir);
        } else {
            throw new Exception('Where is project root directory?');
        }
    }

    public function checkKey()
    {
        if (!$this->make('config')->get('app_key')) {
            throw new Exception('Application key does not exists. First generate app key');
        }
    }

    public function set($name, $value = null)
    {
        $this->container[$name] = $value;
    }

    public function make($name)
    {
        if ($name == 'app') return $this;


        if (!isset($this->container[$name])) {
            throw new Exception("Service '{$name}' not found");
        } elseif (class_exists($name)) {

        }

        if ($this->container[$name] instanceof Closure) {
            $this->container[$name] = $this->container[$name]($this);
        } elseif (is_array($this->container[$name])) {
            foreach ($this->container[$name] as $serviceClassName) {
                if (isset($this->container[$serviceClassName])) {
                    $this->container[$name] = $this->container[$serviceClassName];

                    if ($this->container[$name] instanceof $serviceClassName) {
                        break;
                    } elseif ($this->container[$name] instanceof Closure){
                        $this->container[$name] = $this->container[$name]($this);
                    }
                }
            }
        }

        if (is_string($this->container[$name])) {
            throw new Exception("Service '{$name}' has not booted");
        }

        return $this->container[$name];
    }

    public function prepareObject(string $className)
    {
        $dependencies = [];
        $reflectionClass = new ReflectionClass($className);

        foreach ($reflectionClass->getConstructor()->getParameters() as $param) {
            $dependencies[] = $this->make($param->getType()->getName());
        }

        return $reflectionClass->newInstanceArgs($dependencies);
    }

    private function coreAliasesRegister()
    {
        foreach ([
            'auth' => [\Homeleon\Auth\Auth::class],
            'config' => [\Homeleon\Config\Config::class],
            'db' => [\Homeleon\DB\DB::class, \Homeleon\Contracts\Database\Database::class],
            'request' => [\Homeleon\Http\Request::class],
            'response' => [\Homeleon\Http\Response::class],
            'redis' => [\Redis::class],
            'router' => [\Homeleon\Router\Router::class],
            'route' => [\Homeleon\Router\Route::class],
            'session' => [\Homeleon\Session\Session::class, \Homeleon\Contracts\Session\Session::class],
            'validator' => [\Homeleon\Validation\Validator::class],
        ] as $alias => $services) {
            $this->set($alias, $services);
        }
    }

    public function run()
    {
        $response = $this->make('router')->resolve();

        $className = \Homeleon\Http\Response::class;

        if ($response instanceof $className) {
            if ($response->isRedirect()) {
                $response->setRedirect();
            } else {
                echo $response->getContent();
            }
        } else {
            echo $response;
        }
    }

    public function __get($key)
    {
        return $this->make($key);
    }
}
