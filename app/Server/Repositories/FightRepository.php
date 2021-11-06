<?php

namespace App\Server\Repositories;

use App\Server\Application;
use App\Server\Models\{Fight, Fighter};
use App\Server\Models\Fight\EndFightHandler;

class FightRepository extends BaseRepository
{
    private int $fightId = 1;
    private array $fights = [];

    public function init($fighterProto1, $fighterProto2)
    {
        if ($fighterProto2->fight) {
            $fight = $this->fights[$fighterProto2->fight];
            $fight->addFighter($fighterProto1, 0);

            if (!$fighterProto1->isBot()) {
                $fighterProto1->send(['message' => "<b>Вы присоединились к бою \"{$fight->name}\"</b>"]);
            }

            return false;
        }


        $fight = new Fight($this->fightId, $this->app, new EndFightHandler);
        $fight->name = "Нападение на {$fighterProto2->login}";
        $this->fights[$this->fightId] = $fight;

        // $fighterProto1->curhp = $fighterProto1->maxhp = 200;
        $fight->addFighter($fighterProto1, 0);
        $fight->addFighter($fighterProto2, 1);
        // $fighterProto2->curhp = 1;
        $fight->setPairs();
        $this->fightId++;

        foreach ([$fighterProto1, $fighterProto2] as $f) {
            if ($f->isBot()) continue;
            $f->send(['message' => "<b>Ваш бой \"{$fight->name}\" начался.</b>"]);
        }

        return true;
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
        // if (!isset($this->fights[1])) {
        //     // $this->app->locRepo->attackMonster($user, 1);
        // } else {
        //     $this->app->send($user->getFd(), ['_fight' => $this->fights[$user->fight]?->getData($user->id) ?? null]);
        // }
        if (!$user->fight) return;
        $this->app->send($user->getFd(), ['_fight' => $this->fights[$user->fight]?->getData($user->id) ?? null]);
    }

    public function remove($fightId)
    {
        foreach ($this->fights[$fightId]->fighters as $fighter) {
            if (isset($fighter->user->aggr)) continue;
            $fighter->user->fight = 0;
            $this->app->userRepo->sendUser($fighter->user);
        }
        unset($this->fights[$fightId]);
    }

    public function hit($user, $type)
    {
        $fighter = $this->fights[$user->fight]->fightersById[$user->id];
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
