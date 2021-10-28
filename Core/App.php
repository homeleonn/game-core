<?php

namespace Core;

use Core\Support\Facades\Facade;
use Core\Support\Facades\Response;
use Closure;
use Exception;

class App
{
    protected array $container = [];

    public function __construct()
    {
        $this->coreAliasesRegister();
        $config = require ROOT . '/config/app.php';
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

    public function checkKey()
    {
        if (!$this->make('config')->get('app_key')) {
            throw new Exception('Application key doesn\'t exists. First generate app key');
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

    private function coreAliasesRegister()
    {
        foreach ([
            'auth' => [\Core\Auth\Auth::class],
            'config' => [\Core\Config\Config::class],
            'db' => [\Core\DB\DB::class, \Core\Contracts\Database\Database::class],
            'request' => [\Core\Http\Request::class],
            'response' => [\Core\Http\Response::class],
            'redis' => [\Redis::class],
            'router' => [\Core\Router\Router::class],
            'route' => [\Core\Router\Route::class],
            'session' => [\Core\Session\Session::class, \Core\Contracts\Session\Session::class],
        ] as $alias => $services) {
            // foreach ($services as $service) {
            //     $this->set($service, $alias);
            // }
            $this->set($alias, $services);
        }
    }

    public function run()
    {
        $response = $this->make('router')->resolve();

        $className = \Core\Http\Response::class;

        echo $response instanceof $className ? $response->getContent() : $response;
    }

    public function __get($key)
    {
        return $this->make($key);
    }
}
