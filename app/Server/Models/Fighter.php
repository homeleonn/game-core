<?php

namespace App\Server\Models;

use App\Server\Helpers\HFight;
use Core\Helpers\Common;

class Fighter
{
	const HIT_TURN 						= 2;
	const HITS_COUNT 					= 3;
	const TURN_TIME 					= 4;
	const TURN_TIME_TIMEOUT 	= 10;

	public $user;
	private int $lastEnemyId 	= 0;
	public int $turn 					= 0;
	private int $damage 			= 0;
	private int $fightExp 		= 0;
	private int $kills 				= 0;
	private int $timeoutTicks = 0;
	public array|null $swap 				= [];
	private bool $delay 			= false;
	public Fight $fight;

	public function __construct(User|\stdClass $user, Fight $fight)
	{
		$this->user = $user;
		$this->super_hits = $this->isBot() ? [] : json_decode($this->user->super_hits);
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

	public function hit($type, $app)
	{
		[$damage, $crit, $block, $evasion, $superHit] =  $this->isBot ? false : $this->calcDamage($type);
		$this->resetTimeoutTicks();
		$this->getEnemy()->curhp -= $damage;
		$isFighterDeath = $this->checkFighterDeath();
		$this->send($app, $type, $damage, $crit, $block, $evasion, $superHit);
		if ($this->fight->isFightEnd) return;
		// $this->setDelay();
		// $this->toggleTurn($isFighterDeath);
	}

	public function send($app, $type, $damage, $crit, $block, $evasion, $superHit)
	{
		// d($this);
		// if ($this->isBot()) return;
		$sendData = ['_fight' => [
			'hit' => [
				'defender' => $this->getEnemy()->fId,
				'damage' => $damage,
			],
		]];

		$sendPrivateData = $sendData;
		$privateProps = ['type', 'crit', 'block', 'evasion', 'superHit'];
		foreach ($privateProps as $prop) {
			$sendPrivateData['_fight']['hit'][$prop] = $$prop;
		}

		foreach ($this->fight->fightersById as $fighter) {
			if ($fighter->fId == $this->fId 
				|| $fighter->fId == $this->enemyfId) {
				d($fighter->user);
				$app->send($fighter->getFd(), $sendPrivateData);
			} else {
				$app->send($fighter->getFd(), $sendData);
			}
		}
	}

	public function resetTimeoutTicks() {
		$this->timeoutTicks = 0;
	}

	public function nextTimeoutTicks()
	{
		return ++$this->timeoutTicks;
	}

	public function checkTimeoutDeath()
	{
		return $this->nextTimeoutTicks() >= 3;
	}

	// callback for both enemies
	public function foreachEnemy($cb)
	{
		foreach ([$this, $this->getEnemy()] as $fighter) {
			$cb($fighter);
		}
	}

	public function toggleTurn($isFighterDeath = false)
	{
		if ($this->canSwap($isFighterDeath)) {
			$this->fight->setPairs();
		} else {
			[$turn] = $this->selectTurn();
			$this->swap[self::HIT_TURN] = $turn;
			$this->swap[self::TURN_TIME] = time();
			$this->fight->handleBot($this->getEnemy());
		}
	}

	public function setSwap()
	{
		[$turn, $hitter] = $this->selectTurn();
		$swap = [$this->fId, $this->getEnemy()->fId, $turn, 2, microtime(true)];

		$this->fight->handleBot($hitter);

		$this->foreachEnemy(function ($fighter) use ($swap) {
			$fighter->lastEnemyId = $fighter->getEnemy()->fId;
			$fighter->swap = &$swap;
			$this->fight->swap[$fighter->fId] = &$swap;
		});
	}

	public function canSwap($isEnemyDeath)
	{
		// if hit swap was held or enemy defeated
		if (!--$this->swap[self::HITS_COUNT] || $isEnemyDeath) {
			$this->foreachEnemy(function ($fighter) {
				$fighter->e = $fighter->swap = null;
				unset($this->fight->swap[$fighter->fId]);
				if ($fighter->curhp > 0) {
					$this->fight->addToFreeFighters($fighter);
				}
			});

			return true;
		}

		return false;
	}

	public function calcDamage($type)
	{
		$superHitLevel = $this->checkSuperHit($type);
		[$crit, $evasion, $block, $superHit] = HFight::checkAttack($this, $this->getEnemy(), $type, $superHitLevel);

		// If critical is active then block noesn't work
		if ($evasion || $block) {
			$damage = 0;
			$crit = false;
		} else {
			$damage = mt_rand($this->min_damage, $this->max_damage);
			if ($crit) {
				$damage *= 2;
			}
		}

		if ($this->getEnemy()->curhp < $damage) {
			$damage = $this->getEnemy()->curhp;
		}
		$this->damage += $damage;

		return [$damage, $crit, $block, $evasion, $superHitLevel];
	}

	public function checkFighterDeath($byTimeout = false)
	{
		$fighter = $this->getEnemy();
		if ($fighter->curhp <= 0) {
			$this->kills += 1;
			$fighter->kill();

			return true;
		}

		return false;
	}

	public function kill()
	{
		$this->curhp = 0;
		$this->swap = null;
		$this->fight->isEnd($this);
	}

	public function selectTurn()
	{
		$isPrevEnemy = $this->lastEnemyId == $this->getEnemy()->fId;
		$turn = (!$isPrevEnemy ? mt_rand(0, 0) : ($this->turn ? 0 : 1));
		$this->turn = $this->getEnemy()->turn = $turn;
		[$hitter, $defender] = $this->setRoles($this);

		return [$turn, $hitter, $defender];
	}

	public function checkSuperHit($type)
	{
		foreach ($this->super_hits as $level => $h) {
			if (!isset($h->step)) $h->step = 0;

			if ($h->hit[$h->step] === $type) {
				$h->step++;
				if ($h->step === count($h->hit)) {
					d('SUPER-HIT');
					$h->step = 0;
					return $level;
				}
			} else {
				$h->step = 0;
				if ($h->hit[$h->step] === $type) {
					$h->step++;
				}
			}
		}

		return false;
	}

	public function isHitter()
	{
		return $this->team == $this->turn;
	}

	public function isBot()
	{
		return !is_null($this->aggr);
	}

	public function setRoles()
	{
		return $this->isHitter() ? [$this, $this->getEnemy()] : [$this->getEnemy(), $this];
	}

	public function fightProps($userId)
	{
		$props = ['id', 'fId', 'login', 'level', 'curhp', 'maxhp', 'image', 'team', 'enemyfId', 'turn'];
		if (!$this->isBot()) {
			$props[] = 'sex';
			$props[] = 'swap';
		} else {
			$props[] = 'aggr';
		}
		return Common::propsOnly($this, $props);
	}

	public function __get($key)
	{
		return $this->user->{$key} ?? null;
	}

	public function __call($method, $args)
	{
		return $this->user->{$method}($args);
	}
}