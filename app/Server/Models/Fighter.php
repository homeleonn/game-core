<?php

namespace App\Server\Models;

use App\Server\Helpers\HFight;
use Homeleon\Support\Common;
use stdClass;

class Fighter
{
    const HIT_TURN                = 2;
    const HITS_COUNT              = 3;
    const TURN_TIME               = 4;
    const TURN_TIME_TIMEOUT       = 10;

    public User|stdClass $user;
    public int $lastEnemyfId      = 0;
    public int $turn              = 0;
    public int $damage            = 0;
    public int $fightExp          = 0;
    public int $kills             = 0;
    public int $timeoutTicks      = 0;
    public array|null $swap       = [];
    private bool $delay           = false;
    public int $team;
    public Fight $fight;

    public function __construct(User|stdClass $user, int $team, Fight $fight)
    {
        // $this->user = $user instanceof User ? $user : (object)$user;
        $this->user = $user;
        $this->team = $team;
        $this->super_hits = $this->isBot() ? [] : json_decode($this->user->super_hits);
        $this->fight = $fight;
    }

    public function getEnemy()
    {
        return $this->enemyfId ? $this->fight->fighters[$this->enemyfId] : null;
    }

    public function setEnemy($fighter)
    {
        $this->enemyfId = $fighter->fId;
        $fighter->enemyfId = $this->fId;
    }

    public function clearEnemy()
    {
        $this->enemyfId = null;
        $this->swap = null;
    }

    public function getTimeTurnLeft()
    {
        return empty($this->swap) ? null : $this->swap[self::TURN_TIME] - (time() - self::TURN_TIME_TIMEOUT);
    }

    public function hit($type)
    {
        [$damage, $crit, $block, $evasion, $superHit] = $this->calcDamage($type);
        $this->resetTimeoutTicks();
        $this->getEnemy()->curhp -= $damage;
        HFight::send('hit', $this, $type, $damage, $crit, $block, $evasion, $superHit);
        $isEnemyDeath = $this->checkFighterDeath();
        if ($this->fight->isFightEnd) return;
        // $this->setDelay();
        $this->shiftHit();
        $this->toggleTurn($isEnemyDeath);
    }

    public function resetTimeoutTicks()
    {
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

    public function toggleTurn($isEnemyDeath = false)
    {
        if ($this->needToChangeEnemy($isEnemyDeath)) {
            $this->foreachEnemy(function ($fighter) {
                $this->clearEnemy();
                if ($fighter->isAlive()) {
                    $this->fight->addToFreeFighters($fighter);
                }
            });
            $this->fight->setPairs();
        } else {
            [$turn] = $this->selectTurn();
            $this->swap[self::HIT_TURN] = $turn;
            $this->swap[self::TURN_TIME] = time();
            $this->fight->handleBot($this->getEnemy());
            // HFight::send('swap', $this);
        }
    }

    public function setSwap()
    {
        [$turn, $hitter] = $this->selectTurn();
        $swap = [$this->fId, $this->enemyfId, $turn, 2, time()];

        $this->fight->handleBot($hitter);

        $this->foreachEnemy(function ($fighter) use (&$swap) {
            $fighter->lastEnemyfId = $fighter->enemyfId;
            $fighter->swap = &$swap;
        });
        HFight::send('swap', $this);
    }

    private function needToChangeEnemy($isEnemyDeath)
    {
        return !$this->swap || !$this->hitsExist() || $isEnemyDeath;
    }

    private function shiftHit()
    {
        if ($this->swap) $this->swap[self::HITS_COUNT]--;
    }

    private function hitsExist()
    {
        return $this->swap[self::HITS_COUNT] ?? null;
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
        HFight::send('kill', $this->fight->fighters, $this->fId);
        $this->fight->isEnd($this);
    }

    public function selectTurn()
    {
        $isPrevEnemy = $this->lastEnemyfId == $this->enemyfId;
        // echo $this->turn;
        $turn = $isPrevEnemy ? ($this->turn ? 0 : 1) : mt_rand(0, 1);
        $this->turn = $this->getEnemy()->turn = $turn;
        [$hitter, $defender] = $this->setRoles();

        return [$turn, $hitter, $defender];
    }

    public function _toggleTurn()
    {

    }

    public function checkSuperHit($type)
    {
        foreach ($this->super_hits as $level => $h) {
            if (!isset($h->step)) $h->step = 0;

            if ($h->hit[$h->step] === $type) {
                $h->step++;
                if ($h->step === count($h->hit)) {
                    // echo('SUPER-HIT');
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

    public function isAlive()
    {
        return $this->curhp > 0;
    }

    public function isHitter()
    {
        return $this->enemyfId && $this->team == $this->turn;
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
