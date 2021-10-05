<?php

namespace Core\DosProtection;

use Core\Support\Facades\Config;
use Core\Support\ServiceProvider;

class DosProtectionServiceProvider extends ServiceProvider
{
	public function boot() {}

	public function register()
	{
		$this->app->set('dosprotection', function($app) {
			return new DosProtection(Config::get('throttle'));
		});
	}
}