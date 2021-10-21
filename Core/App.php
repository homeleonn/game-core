<?php

namespace Core;

use Core\Support\Facades\Facade;
use Core\Support\Facades\Response;
use Closure;

class App
{
    protected array $container = [];

    public function __construct()
    {
        $config = require ROOT . '/config/app.php';
        Facade::setFacadeApplication($this, $config['aliases']);
        $servicesInstances = $this->loadServices($config['providers']);
        $this->bootServices($servicesInstances);
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

    public function set($name, $value = null)
    {
        $this->container[$name] = $value;
    }

    public function make($name)
    {
        if ($name == 'app') return $this;

        if (!isset($this->container[$name])) {
            throw new \Exception("Service '{$name}' not found");
        }

        // $isclosure = false;
        // if (is_string($this->container[$name]) && isset($this->container[$this->container[$name]])) {
        //     if ($this->container[$this->container[$name]] instanceof Closure) {
        //        $name = $this->container[$name];
        //        $isclosure = true;
        //     }
        // }

        // if ($isclosure || $this->container[$name] instanceof Closure) {
        //     dd($this->container);
        //     $this->container[$name] = $this->container[$name]($this);
        // }

        if ($this->container[$name] instanceof Closure) {
            $this->container[$name] = $this->container[$name]($this);
        } elseif (is_string($this->container[$name]) && isset($this->container[$this->container[$name]])) {
            $this->container[$name] = $this->container[$this->container[$name]];
        }

        return $this->container[$name];
    }

    public function run()
    {
        $response = $this->make('router')->resolve();

        echo $response instanceof (\Core\Http\Response::class) ? $response->getContent() : $response;
    }

    public function __get($key)
    {
        return $this->make($key);
    }
}
