<?php


namespace Core\Services;


use Core\Response;

class ResponseServiceProvider extends AbstractServiceProvider
{

	public function register()
	{
		$this->app->set('response', function() {
			return new Response();
		});
	}

	public function boot()
	{
		// TODO: Implement boot() method.
	}
}