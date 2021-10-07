<?php

namespace App\Server\Repositories;

use App\Server\Application;
use App\Server\Models\Fight;

class FightRepository
{
	private int $fightId = 0;
	private array $fights = [];

	public function __construct(
		private Application $app
	) {}

	public function init($fighter1, $fighter2)
	{
		// $fight = new Fight();
		// $fight->addFighter($fighter1)
		// 				->addFighter($fighter2);
		// $fight->setPairs();
		// $fight->run();
		// $this->fights[++$fightId] = $fight;
		array_map(function ($fighter) use ($fightId) {
			$this->beforeFight($fighter, $fightId);
		}, [$fighter1, $fighter2]);
	}

	private function beforeFight($fighter, $fightId)
	{
		$fighter->fight = $fightId;
		if (!isset($fighter->aggr)) {
			$this->app->send($fighter->getFd(), ['fight' => 'start']);
		}
	}

	public function addFighter($fighter, $fightId)
	{
		if (!isset($this->fights[$fightId])) return;

		$this->fights[$fightId]
			->addFighter($fighter)
			->setPairs();
	}
}