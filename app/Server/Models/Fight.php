<?php

namespace App\Server\Models;

use Core\Helpers\Common;
use App\Server\Helpers\HFight;
use Core\Support\Facades\App;

class Fight
{
    public int $fightId;
    public array $fighters = [];
    public array $fightersById = [];
    private array $teams = [[], []];
    private array $freeFightersIds = [[], []];
    private array $botsHits = [];
    public array $swap = [];
    public bool $isFightEnd = false;
    private int $winTeam;
    private int $fighterIdCounter = 1;
    private int $startTime;
    private $app;

    private int $activeTeam = 0;
    private int $passiveTeam = 1;

    public function __construct($fightId, $app)
    {
        $this->app = $app;
        $this->fightId = $fightId;
        $this->startTime = time();
    }

    public function cicle()
    {
        $this->checkToggleTurn();
        $this->processBotsTick();
    }

    private function processBotsTick()
    {
        $time = time();
        foreach ($this->botsHits as $botfId => $hit) {
            if ($time < $hit) return;
            unset($this->botsHits[$botfId]);
            $bot = $this->fighters[$botfId];
            [$hitter] = $bot->setRoles();
            $hitter?->hit(mt_rand(1, 3));
        }
    }

    public function addFighter($fighterProto, $team): self
    {
        $fighter = new Fighter($fighterProto, $team, $this);
        $fighter->user->fight = $this->fightId;
        if (!$fighter->isBot()) {
            $this->fightersById[$fighter->id] = $fighter;
            $this->app->send($fighter->user->getFd(), ['fight' => 'start']);
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
            if ($this->noFreeFighters()) return;

            $fighter = $this->getRandomFighter($this->activeTeam);
            $fighter->setEnemy($this->getRandomFighter($this->passiveTeam));
            $fighter->setSwap();
        }
    }

    public function noFreeFighters()
    {
        return empty($this->freeFightersIds[$this->activeTeam]) || empty($this->freeFightersIds[$this->passiveTeam]);
    }

    private function getRandomFighter($team)
    {
        $fighterIdKey = mt_rand(0, count($this->freeFightersIds[$team]) - 1);
        $fighterId         = array_keys($this->freeFightersIds[$team])[$fighterIdKey];
        $fighter             = $this->fighters[$fighterId];

        $this->removeFreeFighter($fighter);

        return $fighter;
    }

    private function removeFreeFighter($fighter)
    {

        if (array_key_exists($fighter->fId, $this->freeFightersIds[$fighter->team])) {
            unset($this->freeFightersIds[$fighter->team][$fighter->fId]);
        }
    }

    private function checkToggleTurn()
    {
        // f - fighter
        foreach ($this->teams[$this->activeTeam] as $f) {
            // echo ($f->getTimeTurnLeft() ?? null);
            if (!$f->swap || $f->getTimeTurnLeft() > 1) continue;

            $passFighter = $f->isHitter() ? $f : $f->getEnemy();
            $death = $passFighter->checkTimeoutDeath();

            if ($death) {
                $passFighter->kill();
                if ($this->isFightEnd) return;
            }

            $this->handleBot($f);
            $f->toggleTurn($death);
        }
    }

    public function handleBot($fighter)
    {
        if ($fighter->isBot() && $fighter->isAlive()) {
            $this->botsHits[$fighter->fId] = $this->monsterDamageTime();
        }
    }

    private function monsterDamageTime()
    {
        return time() + mt_rand(2, 5);
        // return time() + 3;
    }

    public function isEnd($defender)
    {
        if ($this->checkAliveTeam($this->teams[$defender->team])) return false;

        $this->isFightEnd = true;
        $this->winTeam = $defender->getEnemy()->team;
        $this->setStatistics($this->winTeam);
        App::make('game')->fightRepo->remove($this->fightId);

        return true;
    }

    private function setStatistics($winTeam)
    {
        $teamsStatisticts = [[], []];
        $needProps = ['fId', 'login', 'level', 'damage', 'kills'];

        foreach ($this->teams as $idx => $team) {
            $isWinner = $idx == $this->winTeam;
            foreach ($team as $fighter) {
                $teamsStatisticts[$idx][$fighter->fId] = Common::propsOnly($fighter, $needProps);
                $teamsStatisticts[$idx][$fighter->fId]['fightExp'] = $isWinner ? $fighter->damage * 2 : 0;
            }
        }

        // Send statistics to all fight members
        HFight::send('statistics', $this->fighters, $this->startTime, $this->winTeam, $teamsStatisticts);
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
