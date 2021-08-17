<?php

namespace Core\Session;

use Redis;

class Init {
	public function __construct(Redis $redis)
	{
		global $redis, $sessionHandler;
		ini_set('session.save_path', 'tcp://127.0.0.1:6379');
		ini_set('session.serialize_handler', 'php_serialize');

		$sessionHandler = new RedisSessionHandler($redis);
		session_set_save_handler($sessionHandler, true);
		session_start();
	}
}