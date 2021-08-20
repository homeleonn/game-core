<?php

namespace Core;

use Core\Facades\Facade;
use Closure;

class App
{
    protected array $container = [];
    public array $config;

    public function __construct()
    {
    	$this->loadConfig();
    	$servicesInstances = $this->loadServices();
		Facade::setFacadeApplication($this);
		$this->bootServices($servicesInstances);
    }

    protected function loadServices()
    {
        $services = require ROOT . '/config/services.php';
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

    protected function loadConfig()
    {
		$this->config = require ROOT . '/.env.php';
    }

	public function set($name, $value = null)
	{
		$this->container[$name] = $value;
    }

	public function make($name)
	{
        if ($name == 'app') return $this;
        
		if (!isset($this->container[$name])) {
			exit("Service '$name' not found");
		}

		if (!is_object($this->container[$name]) || $this->container[$name] instanceof Closure) {
			$this->container[$name] = $this->container[$name]($this);
		}

		return $this->container[$name];
	}

    public function run()
    {
        $response = $this->make('router')->resolve();
        
        echo $response instanceof \Core\Response ? $response->getContent() : $response;
        // echo $this->make('router')->resolve()->getContent();
    }
}