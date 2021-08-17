<?php

namespace Core\Services;

use Core\Auth;

class AuthServiceProvider extends AbstractServiceProvider
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