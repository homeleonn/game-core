<?php

namespace App\Server\Repositories;

use Exception;
use App\Server\Models\{Fight, Npc, User};
use App\Server\Models\Fight\EndFightHandler;

class FightRepository extends BaseRepository
{
    private int $fightId = 1;
    /**
     * @var Fight[]
     */
    private array $fights = [];

    public function init(User|Npc $fighterProto1, User|Npc $fighterProto2): bool
    {
        if ($fighterProto2->fight) {
            $fight = $this->fights[$fighterProto2->fight];
            $fight->addFighter($fighterProto1, 0);

            if (!$fighterProto1->isBot()) {
                $fighterProto1->send(['message' => "<b>Вы присоединились к бою \"{$fight->name}\"</b>"]);

                try {
                    $newFighter = $fight->getFighterById($fighterProto1->id);
                    $this->sendTo($fight->fighters, ['new_fighter' => [$newFighter->fId => $newFighter->fightProps()]]);
                } catch (Exception $e) {}
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


        $this->sendTo([$fighterProto1, $fighterProto2], ['message' => "<b>Ваш бой \"{$fight->name}\" начался.</b>"]);

        return true;
    }


    /**
     * @param User[] $users
     * @param string|array $message
     * @return void
     */
    private function sendTo(array $users, string|array $message): void
    {
        foreach ($users as $user) {
            if ($user->isBot()) {
                continue;
            }

            $user->send($message);
        }
    }

    private function createBots(Npc $proto, int $team, int $fightId, int $count = 1): void
    {
        if ($count <= 0) {
            return;
        }

        $fight = $this->fights[$fightId];

        while ($count--) {
            $fight->addFighter($proto, $team);
        }
    }

    public function getById(User $user): void
    {
        // if (!isset($this->fights[1])) {
        //     // $this->app->locRepo->attackMonster($user, 1);
        // } else {
        //     $this->app->send($user->getFd(), ['_fight' => $this->fights[$user->fight]?->getData($user->id) ?? null]);
        // }
        if (!$user->fight || !isset($this->fights[$user->fight])) return;
        $this->app->send($user->getFd(), ['_fight' => $this->fights[$user->fight]?->getData($user->id) ?? null]);
    }

    public function remove($fightId): void
    {
        foreach ($this->fights[$fightId]->fighters as $fighter) {
            if (isset($fighter->user->aggr)) continue;
            $fighter->user->fight = 0;
            $this->app->userRepo->sendUser($fighter->user);
        }
        unset($this->fights[$fightId]);
    }

    public function hit($user, $type): void
    {
        $fighter = $this->fights[$user->fight]->fightersById[$user->id];
        if (!$fighter->isHitter()) {
            d('not hitter');
            return;
        }
        $fighter->hit($type);
    }

    public function cycle(): void
    {
        foreach ($this->fights as $fight) {
            $fight->cycle();
        }
    }
}
