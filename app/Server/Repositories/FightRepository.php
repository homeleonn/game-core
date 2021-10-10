<?php

namespace App\Server\Repositories;

use App\Server\Application;
use App\Server\Models\{Fight, Fighter};

class FightRepository
{
	private int $fightId = 1;
	private array $fights = [];

	public function __construct(
		private Application $app
	) {}

	public function init($fighter1, $fighter2)
	{
		// $this->fightId++;
		$fight = new Fight(1);
		$fighter1 = new Fighter($fighter1, $fight);
		$fighter2 = new Fighter($fighter2, $fight);
		$fighter1->team = 0;
		$fighter2->team = 1;

		$fight->addFighter($fighter1)
						->addFighter($fighter2);
		$fight->setPairs();
		// $fight->run();
		$this->fights[$this->fightId] = $fight;
		// array_walk([$fighter1, $fighter2], function (&$fighter) {
		// 	$this->beforeFight($fighter, $this->fightId);
		// });
		$this->beforeFight($fighter1, $this->fightId);
		$this->beforeFight($fighter2, $this->fightId);
	}

	private function beforeFight($fighter, $fightId)
	{
		$fighter->user->fight = $fightId;
		if (!$fighter->isBot()) {
			$this->app->send($fighter->user->getFd(), ['fight' => 'start']);
		}
	}

	public function addFighter($fighter, $fightId)
	{
		if (!isset($this->fights[$fightId])) return;

		$this->fights[$fightId]
			->addFighter($fighter)
			->setPairs();
	}

	public function getById($user)
	{
		if (!isset($this->fights[1])) {
			$this->app->locRepo->attackMonster($user, 1);
		}
		$this->app->send($user->getFd(), ['_fight' => $this->fights[1]?->getData($user->id) ?? null]);
	}

	public function remove($fightId)
	{
		echo "Remove Fight: $fightId";
		unset($this->fights[$fightId]);
	}

	public function hit($user, $type)
	{
		d(array_keys($this->fights));
		$fighter = $this->fights[1]->fightersById[$user->id];
		// d($this->fights[1]->fightersById);
		if (!$fighter->isHitter()) {
			d('not hitter');
			return;
		}
		$fighter->hit($type, $this->app);
	}

	public function cicle()
	{
		foreach ($this->fights as $fight) {
			$fight->cicle();
		}
	}
}