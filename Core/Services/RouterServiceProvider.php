<?php


namespace App\Core\Services;

use App\Core\Router;
use App\Core\Request;
use App\Core\Response;

class RouterServiceProvider extends AbstractServiceProvider
{
	public function boot()
	{
//		$this->app->set('router', new Router($this->app->get('request'), $this->app->get('response')));
	}

	public function register()
	{
		$this->app->set(Router::class, function($app) {
			return new Router(
				$app->make(Request::class),
				$app->make(Response::class)
			);
		});
	}
}