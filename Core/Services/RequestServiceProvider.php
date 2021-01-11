<?php


namespace App\Core\Services;

use App\Core\App;
use App\Core\Request;


class RequestServiceProvider extends AbstractServiceProvider
{
	public function register()
	{
		$this->app->set(Request::class, function() {
			return new Request();
		});
	}

	public function boot()
	{
//		$this->app->set('request', new Request());
	}
}