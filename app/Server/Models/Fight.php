<?php

namespace App\Server\Models;

use App\Server\Application;
use Exception;
use Homeleon\Support\Common;
use App\Server\Helpers\HFight;
use App\Server\Models\Fight\EndFightHandler;

class Fight
{
    const BOT_HIT_TIMEOUT_MIN = 2;
    const BOT_HIT_TIMEOUT_MAX = 4;
    public string $name;
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
    private Application $game;
    private EndFightHandler $endFightHandler;

    private int $activeTeam = 0;
    private int $passiveTeam = 1;

    public function __construct($fightId, Application $game, EndFightHandler $endFightHandler)
    {
        $this->game = $game;
        $this->fightId = $fightId;
        $this->startTime = time();

        $this->endFightHandler = $endFightHandler;
    }

    public function getFighterById(int $id): ?Fighter
    {
        foreach ($this->fighters as $fighter) {
            if ($fighter->id == $id && !$fighter->isBot()) return $fighter;
        }

        throw new Exception("Fighter with id '$id' not found in fight $this->fightId");
    }

    public function cycle(): void
    {
        $this->checkToggleTurn();
        $this->processBotsTick();
    }

    private function processBotsTick(): void
    {
        $time = time();
        foreach ($this->botsHits as $botfId => $hit) {
            if ($time < $hit) continue;
            unset($this->botsHits[$botfId]);
            $bot = $this->fighters[$botfId];
            [$hitter] = $bot->setRoles();
            $hitter?->hit(mt_rand(1, 3));
        }
    }

    public function addFighter($fighterProto, $team): self
    {
        $fighterProto->restore();
        $fighterProto->fight = $this->fightId;
        $fighterProto->curhp = floor($fighterProto->curhp);
        $fighter = new Fighter($fighterProto, $team, $this);
        if (!$fighter->isBot()) {
            $this->fightersById[$fighter->id] = $fighter;
            $this->game->send($fighter->user->getFd(), ['fight' => 'start']);
        }
        $fighter->fId = $this->fighterIdCounter++;
        $this->fighters[$fighter->fId] = $fighter;
        $this->teams[$fighter->team][$fighter->fId] = $fighter;
        $this->addToFreeFighters($fighter);

        return $this;
    }

    public function addToFreeFighters($fighter): void
    {
        $this->freeFightersIds[$fighter->team][$fighter->fId] = null;
    }

    public function setPairs(): void
    {
        $allFreeTeamFightersIds = $this->freeFightersIds[$this->activeTeam];

        for ($i = 0; $i < $allFreeTeamFightersIds; $i++) {
            if ($this->noFreeFighters()) break;

            $fighter = $this->getRandomFighter($this->activeTeam);
            $fighter->setEnemy($this->getRandomFighter($this->passiveTeam));
            $fighter->setSwap();
        }
    }

    public function noFreeFighters(): bool
    {
        return empty($this->freeFightersIds[$this->activeTeam]) || empty($this->freeFightersIds[$this->passiveTeam]);
    }

    private function getRandomFighter($team): Fighter
    {
        $fighterIdKey   = mt_rand(0, count($this->freeFightersIds[$team]) - 1);
        $fighterId      = array_keys($this->freeFightersIds[$team])[$fighterIdKey];
        $fighter        = $this->fighters[$fighterId];

        $this->removeFreeFighter($fighter);

        return $fighter;
    }

    private function removeFreeFighter($fighter): void
    {
        if (array_key_exists($fighter->fId, $this->freeFightersIds[$fighter->team])) {
            unset($this->freeFightersIds[$fighter->team][$fighter->fId]);
        }
    }

    private function checkToggleTurn(): void
    {
        /**
         * @var Fighter $f
         */
        foreach ($this->teams[$this->activeTeam] as $fighter) {
            if (!$fighter->swap || $fighter->getTimeTurnLeft() > 1) continue;

            $passFighter = $fighter->isHitter() ? $fighter : $fighter->getEnemy();
            $death = $passFighter->checkTimeoutDeath();

            if ($death) {
                $passFighter->kill();
                if ($this->isFightEnd) break;
            }

            $this->handleBot($fighter);
            $fighter->toggleTurn($death);
        }
    }

    public function handleBot($fighter): void
    {
        if ($fighter->isBot() && $fighter->isAlive()) {
            $this->botsHits[$fighter->fId] = $this->monsterDamageTime();
        }
    }

    private function monsterDamageTime(): int
    {
        return time() + mt_rand(self::BOT_HIT_TIMEOUT_MIN, self::BOT_HIT_TIMEOUT_MAX);
        // return time() + 3;
    }

    public function isEnd($defender): bool
    {
        if ($this->checkAliveTeam($this->teams[$defender->team])) return false;

        $this->isFightEnd = true;
        $this->winTeam = $defender->getEnemy()->team;
        $this->setStatistics($this->winTeam);
        $this->game->fightRepo->remove($this->fightId);

        return true;
    }

    private function setStatistics($winTeam): void
    {
        $teamsStatistics = [[], []];
        $needProps = ['fId', 'login', 'level', 'damage', 'kills'];

        foreach ($this->teams as $idx => $team) {
            $isWinner = $idx == $this->winTeam;
            foreach ($team as $fighter) {
                $additionExp = $isWinner ? $fighter->damage * 2 : 0;
                if (!$fighter->isBot()) $fighter->addExp($additionExp);
                $teamsStatistics[$idx][$fighter->fId] = Common::propsOnly($fighter, $needProps);
                $teamsStatistics[$idx][$fighter->fId]['fightExp'] = $additionExp;
                $teamsStatistics[$idx][$fighter->fId]['id'] = (int)$fighter->getId();

                $this->endFightHandler->processFighter($fighter, $isWinner);
            }
        }

        // Send statistics to all fight members
        HFight::send('statistics', $this->fighters, $this->startTime, $this->winTeam, $teamsStatistics);
    }

    private function checkAliveTeam($team): bool
    {
        foreach ($team as $fighter) {
            if ($fighter->curhp > 0) return true;
        }

        return false;
    }

    public function getData($userId): array
    {
        return [
            'fighters' => array_map(function (Fighter $fighter) use ($userId) {
                return $fighter->fightProps($userId);
            }, $this->fighters),
        ];
    }
}
