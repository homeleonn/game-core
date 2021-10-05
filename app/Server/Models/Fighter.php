<?php

namespace App\Server\Models;

class Fighter
{
	const HIT_TURN 						= 2;
	const HITS_COUNT 					= 3;
	const TURN_TIME 					= 4;
	const TURN_TIME_TIMEOUT 	= 10;

	private int $lastEnemyId 	= 0;
	private int $turn 				= 0;
	private int $damage 			= 0;
	private int $fightExp 		= 0;
	private int $kills 				= 0;
	private int $timeoutTicks = 0;
	private array $swap 			= [];
	private bool $delay 			= false;
	private bool $delay 			= false;
	private Fight $fight;

	public function __construct(User $user, Fight $fight)
	{
		$this->user = $user;
		$this->fight = $fight;
	}

	public function getEnemy()
	{
		return $this->fight->fighters[$this->enemyfId];
	}

	public function setEnemy($fighter)
	{
		$this->enemyfId = $fighter->fId;
		$fighter->enemyfId = $this->fId;
	}

	public function getTimeTurnLeft()
	{
		return !empty($this->swap) ? null : round($this->swap[self::TURN_TIME] - (time() - self::TURN_TIME_TIMEOUT));
	}

	public function hit($type)
	{
		[$damage, $crit, $block, $evasion, $superHit] = $this->calcDamage($type);
		$isFighterDeath = $this->checkFighterDeath();
		if ($this->fight->isFightEnd) return;
		$this->setDelay();
		$this->toggleTurn($isFighterDeath);
	}

	public function nextTimeoutTicks() {
		return ++$this->timeoutTicks;
	}

	public function checkTimeoutDeath() {
		return $this->nextTimeoutTicks() >= 3;
	}

	// callback for both enemies
	public function foreachEnemy($cb) {
		foreach ([$this, $this->getEnemy()] as $fighter) {
			$cb($fighter);
		}
	}

	public function toggleTurn($isFighterDeath = false) {
		if ($this->canSwap($isFighterDeath)) {
			$this->fight->setPairs();
		} else {
			[$turn] = $this->selectTurn();
			$this->swap[self::HIT_TURN] = $turn;
			$this->swap[self::TURN_TIME] = time();
			$this->fight->handleBot($this->getEnemy());
		}
	}

	public function __get($key)
	{
		return $this->user->{$key};
	}
}