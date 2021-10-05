<?php

namespace Core\Session;

use Redis;
use Core\Support\ServiceProvider;

class SessionServiceProvider extends ServiceProvider
{
	public function boot() {}

	public function register()
	{
		$redis = new Redis;
		$redis->connect('127.0.0.1', '6379');
		ini_set('session.save_path', 'tcp://127.0.0.1:6379');
		ini_set('session.serialize_handler', 'php_serialize');

		$sessionHandler = new RedisSessionHandler($redis);
		session_set_save_handler($sessionHandler, true);
		session_start();

		$this->app->set('storage', function($app) use ($sessionHandler) {
			return new Storage($sessionHandler);
		});
	}
}