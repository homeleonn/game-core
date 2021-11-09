<?php

namespace App\Server\Models\Fight;

use App\Server\Models\User;
use App\Server\Models\Unit;
use Homeleon\Support\Common;
use Homeleon\Support\Facades\DB;
use App\Server\Models\Quest\UserQuest;

class EndFightHandler
{
    private $fighter;
    private bool $isWinner;

    public function __construct()
    {
        $this->questItems = repo('quest')->questItems;
    }

    public function processFighter($fighter, $isWinner)
    {
        $this->fighter = $fighter;
        $this->fighter->user->fight = 0;
        $this->isWinner = $isWinner;

        return $this->fighter->isBot()
             ? $this->processBot()
             : $this->processUser();
    }

    public function processUser()
    {
        if ($this->isWinner) {
            $this->fighter->win += 1;
        } else {
            $this->fighter->defeat += 1;
        }

        $this->fighter->last_restore = time();
        // $this->fighter->sendChars(\App::make('game'), ['curhp', 'maxhp', 'win', 'defeat', 'fight']);
        $this->fighter->save();
        $this->fighter->send(['message' => "<b>Ваш бой \"{$this->fighter->fight->name}\" завершился.</b>"]);

        $this->drop($this->isWinner, $this->isWinner);
    }

    public function processBot()
    {
        if ($this->fighter->curhp <= 0) {
            repo('npc')->kill($this->fighter);
        } else {
            $this->fighter->curhp = $this->fighter->maxhp;
            \App::make('game')->sendToLoc($this->fighter->loc_id, ['monsterGotFree' => $this->fighter->id]);
        }
    }

    private function drop($fighter, $isWinner)
    {
        // Пока считается дроп тому кто нанес последний удар,
        // что бы считать по тому кто нанес больше урона
        // в бою нужно каждому участнику боя записывать кто и насколько его ударил,
        // затем сравнивать
        if (!$this->isWinner || empty($this->fighter->kill['npc'])) return;

        $userQuests = $this->getUserQuests($this->fighter->id);
        $userItems = $this->fighter->user->itemsByItemId;

        $userDrop = [];
        foreach ($this->fighter->kill['npc'] as $npcId => $count) {
            if (!$npcDrop = repo('drop')->getByNpc($npcId)) continue;

            while ($count--) {
                foreach ($npcDrop as $drop) {
                    if ($this->passQuestDrop($drop, $userQuests, $userItems)) {
                        continue;
                    }
                    if (isDrop($drop->chance)) {
                        $userDrop[] = [
                            'npc_id' => $drop->npc_id,
                            'item_id' => $drop->item_id,
                            'count' => mt_rand($drop->min, $drop->max),
                        ];
                    }
                }
            }
        }

        if ($userDrop) $this->sendDropToUser($this->fighter->user, $userDrop);
    }

    private function getUserQuests($userId)
    {
        return UserQuest::where('user_id', $userId)
                               ->andWhere('completed', 0)
                               ->andWhere('step', 0)
                               ->by('quest_id')
                               ->all();
    }

    // pass drop quest item for user who doesn't have this quest
    // or already has collected all quest items
    private function passQuestDrop($drop, $userQuests, $userItems)
    {
        if ($questItem = $this->questItems[$drop->item_id] ?? null) {
            if (!$this->hasQuest($userQuests, $questItem) || $this->alreadyCollects($drop->item_id, $userItems, $questItem)) return true;
        }

        return false;
    }

    private function hasQuest($userQuests, $questItem)
    {
        return isset($userQuests[$questItem['quest_id']]);
    }

    private function alreadyCollects($itemId, $userItems, $questItem)
    {
        return $this->fighter->countOfItems($itemId) >= $questItem['count'];
    }

    public function isQuestItem($item_id)
    {
        return repo('quest')->isQuestItem($itemId);
    }

    private function sendDropToUser(User $user, array $userDrop)
    {
        $dropMessage = [];
        repo('item')->exchangeWithUser($user, $userDrop, function ($drop) use (&$dropMessage) {
            $dropMessage[] = [
                'item_id'   => $drop['item_id'],
                'npc_id'    => $drop['npc_id'],
                'npc_name'  => repo('npc')->get($drop['npc_id'])->name,
                'name'      => repo('item')->getItemById($drop['item_id'])->name,
                'count'     => $drop['count']
            ];
        });

        if (!empty($dropMessage)) {
            $user->send(['drop' => $dropMessage]);
        }
    }
}
