<?php

namespace Core\Services;

use Core\DB\SafeMySQL;
use Core\Facades\Config;

class DatabaseServiceProvider extends AbstractServiceProvider
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