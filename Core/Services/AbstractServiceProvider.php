<?php


namespace App\Core\Services;

use App\Core\App;


abstract class AbstractServiceProvider
{
	protected App $app;
	public function __construct(App $app)
	{
		$this->app = $app;
	}

	abstract public function register();
	abstract public function boot();
}