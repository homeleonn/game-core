<?php

namespace Core\Services;

use Core\Config;

class ConfigServiceProvider extends AbstractServiceProvider
{
	public function boot()
	{

	}

	public function register()
	{
		$this->app->set('config', function($app) {
			return new Config($app->config);
		});
	}
}