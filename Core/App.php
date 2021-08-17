<?php


namespace Core;

use Core\Facades\Facade;
use Closure;

class App
{
    private array $container = [];

    public function __construct()
    {
    	$this->loadServices();
		Facade::setFacadeApplication($this);
    }

    private function loadServices()
    {
        $services = require '../config/services.php';

        foreach ($services as $service) {
			(new $service($this))->register();
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