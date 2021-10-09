<?php

namespace App\Server\Models;

use Core\Helpers\Common;

class Fight
{
	public array $fighters = [];
	public array $fightersById = [];
	private array $teams = [[], []];
	private array $freeFightersIds = [[], []];
	private array $botsHits = [];
	public array $swap = [];
	public bool $isFightEnd = false;
	private int $winTeam;
	private int $fighterIdCounter = 1;
	private int $start;

	private int $activeTeam = 0;
	private int $passiveTeam = 1;

	public function __construct()
	{
		$this->start = microtime(true);
	}

	public function addFighter(Fighter $fighter): self
	{
		if (!$fighter->isBot()) {
			$this->fightersById[$fighter->id] = $fighter;
		}
		$fighter->fId = $this->fighterIdCounter++;
		$this->fighters[$fighter->fId] = $fighter;
		$this->teams[$fighter->team][$fighter->fId] = $fighter;
		$this->addToFreeFighters($fighter);

		return $this;
	}

	public function addToFreeFighters($fighter)
	{
		$this->freeFightersIds[$fighter->team][$fighter->fId] = null;
	}

	public function setPairs()
	{
		$allFreeTeamFightersIds = $this->freeFightersIds[$this->activeTeam];

		for ($i = 0; $i < $allFreeTeamFightersIds; $i++) {
			if (empty($this->freeFightersIds[$this->activeTeam])
			 || empty($this->freeFightersIds[$this->passiveTeam])) return;

			$fighter = $this->getRandomFighter($this->activeTeam);
			$fighter->setEnemy($this->getRandomFighter($this->passiveTeam));
			$fighter->setSwap();
		}
	}

	private function getRandomFighter($team)
	{
		$fighterIdKey = mt_rand(0, count($this->freeFightersIds[$team]) - 1);
		$fighterId = array_keys($this->freeFightersIds[$team])[$fighterIdKey];
		// d($this->freeFightersIds, $team, $id);
		// $fighterId 		= $this->freeFightersIds[$team][$id];
		$fighter 			= $this->teams[$team][$fighterId];

		$this->removeFreeFighter($fighter, $fighterId);

		return $fighter;
	}

	private function removeFreeFighter($fighter, $fighterIdKey = null)
	{
		$key = $fighterIdKey ?? $fighter->fId;

		if (array_key_exists($key, $this->freeFightersIds[$fighter->team])) {
			unset($this->freeFightersIds[$fighter->team][$key]);
		}
	}

	private function checkToggleTurn()
	{
		// f - fighter
		foreach ($this->teams[$this->activeTeam] as $f) {
			if (!$f->swap || $f->getTimeTurnLeft() > 1) continue;

			$passFighter = $f->isHitter() ? $f : $f->getEnemy();
			$death = $passFighter->checkTimeoutDeath();

			if ($death) {
				$passFighter->kill();
			}

			$this->handleBot($f);
			$f->toggleTurn($death);
		}
	}

	public function handleBot($fighter)
	{
		if (isset($fighter->isBot)) {
			$this->botsHits[$fighter->fId] = $this->monsterDamageTime();
		}
	}

	private function monsterDamageTime()
	{
		return time() + 1;
	}

	public function isEnd($defender)
	{
		if (!$this->checkAliveTeam($this->teams[$defender->team])) return false;

		// stopAllTimers();
		$this->isFightEnd = true;
		$this->winTeam = $defender->getEnemy()->team;
		$this->setStatistics($this->winTeam);

		return true;
	}

	private function setStatistics($winTeam)
	{
		$teamsStatisticts = [[], []];
		$needProps = ['fId', 'login', 'level', 'damage', 'kills'];

		foreach ($this->teams as $idx => $team) {
			$isWinner = $idx == $this->winTeam;
			foreach ($team as $fighter) {
				$teamsStatisticts[$idx][$fighter->fId] = Common::propsOnly($fighter, ['fId', 'login', 'level', 'damage', 'kills']);
				$teamsStatisticts[$idx][$fighter->fId]['fightExp'] = $isWinner ? $fighter->damage * 2 : 0;
			}
		}

		// Send statistics to all fight members
		// [start, winTeam, teamsStatisticts]
	}

	private function checkAliveTeam($team)
	{
		foreach ($team as $fighter) {
			if ($fighter->curhp > 0) return true;
		}

		return false;
	}

	public function getData($userId)
	{
		return [
			'fighters' => array_map(function ($f) use ($userId) {
				return $f->fightProps($userId);
			}, $this->fighters),
		];
	}
}