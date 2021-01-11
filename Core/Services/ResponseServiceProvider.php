<?php


namespace App\Core\Services;


use App\Core\Response;

class ResponseServiceProvider extends AbstractServiceProvider
{

	public function register()
	{
		$this->app->set(Response::class, function() {
			return new Response();
		});
	}

	public function boot()
	{
		// TODO: Implement boot() method.
	}
}