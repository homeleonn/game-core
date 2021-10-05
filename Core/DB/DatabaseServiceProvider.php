<?php

namespace Core\DB;

use Core\Support\Facades\Config;
use Core\Support\ServiceProvider;

class DatabaseServiceProvider extends ServiceProvider
{
	public function boot()
	{
	}

	public function register()
	{
		$this->app->set('db', function($app) {
			return new SafeMySQL(Config::get('db'));
		});
	}
}