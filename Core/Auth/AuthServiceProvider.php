<?php

namespace Core\Auth;

use Core\Support\ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{

	public function register()
	{
		$this->app->set('auth', function() {
			return new Auth();
		});
	}

	public function boot()
	{
		// TODO: Implement boot() method.
	}
}