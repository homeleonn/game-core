<?php

namespace Core\Socket;

class DosProtection
{
	private $ips = [];
	private $limit; // limit connections per minute by ip

	public function __construct(int $limit = 60)
	{
		$this->limit = $limit;
	}

	public function isValid($ip)
	{
		if (!isset($this->ips[$ip])) {
			$this->ips[$ip] = [
				'count' => 1,
				'time' 	=> time()
			];

			return true;
		}

		return ++$this->ips[$ip]['count'] < $this->limit;
	}
}