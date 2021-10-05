<?php

namespace App\Server\Repositories;

use App\Application;

class FightRepository
{
	public function __construct(
		private App $app;
	) {}

	public function init(int $subjectId, bool $isNpc = true)
	{
		$this->checkSubject($subjectId, $isNpc);
	}

	private function checkSubject(int $subjectId, bool $isNpc = true)
	{
		$this->checkSubject($subjectId, $isNpc);
	}
}