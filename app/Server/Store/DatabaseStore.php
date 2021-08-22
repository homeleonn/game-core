<?php

namespace App\Server\Store;

use App\Server\Contracts\StoreContract;

class DatabaseStore implements StoreContract
{
	private $db;

	public function __construct($db)
	{
		$this->db = $db;
	}

	public function get(string $key)
	{
		return $this->db->getOne("SELECT value FROM storage WHERE key = ?s", $key);
	}

	public function set(string $key, $value)
	{
		$this->db->query("INSERT INTO storage (key, value) VALUES ({$key}, {$value}) ON DUPLICATE KEY UPDATE value = {$value}");
	}

	public function del(string $key)
	{
		$this->db->query("DELETE FROM storage WHERE key = {$key}");
	}
}