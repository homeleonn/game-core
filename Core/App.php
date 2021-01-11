<?php


namespace App\Core;


class App
{
    public Router $router;
    public Request $request;
    public Response $response;

    private array $container = [];

    public function __construct()
    {
    	$this->loadServices();
        $this->router = $this->make(Router::class);
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
		if (!isset($this->container[$name])) {
			exit("Service `$name` not found");
		}

		if (!($this->container[$name] instanceof $name)) {
			$this->container[$name] = $this->container[$name]($this);
		}

		return $this->container[$name];
	}

    public function run()
    {
        echo $this->make(Router::class)->resolve();
    }
}