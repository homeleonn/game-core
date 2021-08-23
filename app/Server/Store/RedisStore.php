<?php

namespace App\Server\Store;

use App\Server\Contracts\StoreContract;

class RedisStore implements StoreContract
{
	private $redis;

	public function __construct($redis)
	{
		$this->redis = $redis;
	}

	public function get(string $key)
	{
		return $this->redis->get($key);
	}

	public function set(string $key, $value)
	{
		return $this->redis->set($key, $value);
	}

	public function del(string $key)
	{
		return $this->redis->del($key);
	}
}