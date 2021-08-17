<?php


namespace Core\Services;

use Core\Router;
use Core\Request;
use Core\Response;

class RouterServiceProvider extends AbstractServiceProvider
{
	public function boot()
	{
//		$this->app->set('router', new Router($this->app->get('request'), $this->app->get('response')));
	}

	public function register()
	{
		$this->app->set('router', function($app) {
			return new Router(
				$app->make('request'),
				$app->make('response')
			);
		});
	}
}