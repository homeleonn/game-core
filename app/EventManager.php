<?php

namespace App;

use Redis;

class EventManager
{
	private Redis $redis;
	public int $fd;

	public function __construct(Redis $redis, int $fd)
	{
		$this->redis 	= $redis;
		$this->fd 		= $fd;
	}

	public function add($event): void
	{
		$timeout = 3;
		$this->redis->zadd(
			'events', 
			(str_replace('.', '', round(microtime(true) + $timeout, 4))), 
			json_encode($event));
	}

	public function say($data): void
	{
		echo "Event manager say: ", $data, "\n";
	}
}