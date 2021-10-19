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

    public function init($fighterProto1, $fighterProto2)
    {
        // $this->fightId++;
        $fight = new Fight($this->fightId, $this->app);
        $this->fights[$this->fightId] = $fight;

        $fighterProto1->curhp = $fighterProto1->maxhp = 200;
        $fight->addFighter($fighterProto1, 1);
        $this->createBots($fighterProto2, 1, $this->fightId, 4);
        $this->createBots($fighterProto2, 0, $this->fightId, 4);
        // $this->createBots($fighterProto2, 0, $this->fightId, 1);
        $fight->setPairs();
    }

    private function createBots($proto, $team, $fightId, $count = 1)
    {
        if ($count <= 0) return;
        $fight = $this->fights[$fightId];
        while ($count--) {
            $fight->addFighter($proto, $team);
        }
    }

    public function addFighter($fighter, $fightId)
    {
        if (!isset($this->fights[$fightId])) return;
        $this->fights[$fightId]->addFighter($fighter);
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
        // echo "Remove Fight: $fightId";
        unset($this->fights[$fightId]);
    }

    public function hit($user, $type)
    {
        // d(array_keys($this->fights));
        $fighter = $this->fights[1]->fightersById[$user->id];
        // d($this->fights[1]->fightersById);
        if (!$fighter->isHitter()) {
            d('not hitter');
            return;
        }
        $fighter->hit($type);
    }

    public function cicle()
    {
        foreach ($this->fights as $fight) {
            $fight->cicle();
        }
    }
}
