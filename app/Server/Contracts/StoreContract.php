<?php

namespace App\Server\Contracts;

interface StoreContract
{
	public function get(string $key);

	public function set(string $key, $value);

	public function del(string $key);
}