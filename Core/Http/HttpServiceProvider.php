<?php

namespace Core\Http;

use Core\Support\ServiceProvider;

class HttpServiceProvider extends ServiceProvider
{
	public function register()
	{
		$this->app->set('request', function() {
			return new Request();
		});
		
		$this->app->set('response', function() {
			return new Response();
		});
	}

	public function boot()
	{
		// TODO: Implement boot() method.
	}
}