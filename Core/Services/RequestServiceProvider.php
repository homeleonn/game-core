<?php


namespace Core\Services;

use Core\App;
use Core\Request;


class RequestServiceProvider extends AbstractServiceProvider
{
	public function register()
	{
		$this->app->set('request', function() {
			return new Request();
		});
	}

	public function boot()
	{
//		$this->app->set('request', new Request());
	}
}