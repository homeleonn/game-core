<?php

namespace App\Server\Models\Fight;

use App\Server\Models\User;
use Homeleon\Support\Common;
use Homeleon\Support\Facades\DB;
use App\Server\Models\Quest\UserQuest;

class EndFightHandler
{
    public function __construct()
    {
        $this->questItems = repo('quest')->questItems;
    }

    public function processFighter($fighter, $isWinner)
    {
        $fighter->user->fight = 0;

        return $fighter->isBot()
             ? $this->processBot($fighter, $isWinner)
             : $this->processUser($fighter, $isWinner);
    }

    public function processUser($fighter, $isWinner)
    {
        if ($isWinner) {
            $fighter->win += 1;
        } else {
            $fighter->defeat += 1;
        }

        $fighter->last_restore = time();
        // $fighter->sendChars(\App::make('game'), ['curhp', 'maxhp', 'win', 'defeat', 'fight']);
        $fighter->save();
        $fighter->send(['message' => "<b>Ваш бой \"{$fighter->fight->name}\" завершился.</b>"]);

        $this->drop($fighter, $isWinner);
    }

    public function processBot($fighter)
    {
        if ($fighter->curhp <= 0) {
            repo('npc')->kill($fighter);
        } else {
            $fighter->curhp = $fighter->maxhp;
            \App::make('game')->sendToLoc($fighter->loc_id, ['monsterGotFree' => $fighter->id]);
        }
    }

    private function drop($fighter, $isWinner)
    {
        // Пока считается дроп тому кто нанес последний удар,
        // что бы считать по тому кто нанес больше урона
        // в бою нужно каждому участнику боя записывать кто и насколько его ударил,
        // затем сравнивать
        if (!$isWinner || empty($fighter->kill['npc'])) return;

        $userQuests = $this->getUserQuests($fighter->id);
        $userItems = $fighter->user->itemsByItemId;

        foreach ($fighter->kill['npc'] as $npcId => $count) {
            $npcDrop = repo('drop')->getByNpc($npcId);
            $userDrop = [];

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

        $this->sendDropToUser($fighter->user, $userDrop);
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
        return count($userItems[$itemId] ?? []) >= $questItem['count'];
    }

    public function isQuestItem($item_id)
    {
        return repo('quest')->isQuestItem($itemId);
    }

    private function sendDropToUser(User $user, array $userDrop)
    {
        if (empty($userDrop)) return;

        $update = $insert = $dropMessage = [];
        foreach ($userDrop as $drop) {
            // If exists stackable item then update count else insert new item
            $userItem = $user->getItemByItemId($drop['item_id']);
            if ($userItem && $userItem->stackable) {
                $update[$userItem->id] = ($update[$userItem->id] ?? 0) + $drop['count'];
            } else {
                $insert[$drop['item_id']] = ($insert[$drop['item_id']] ?? 0) + $drop['count'];
            }

            $dropMessage[] = [
                'item_id'   => $drop['item_id'],
                'npc_id'    => $drop['npc_id'],
                'npc_name'  => repo('npc')->get($drop['npc_id'])->name,
                'name'      => repo('item')->getItemById($drop['item_id'])->name,
                'count'     => $drop['count']
            ];
        }

        $this->updateUserItemsByDrop($update);
        $this->insertUserItemsByDrop($user->id, $insert);

        $user->send(['drop' => $dropMessage]);
        $user->loadItems();
    }

    private function updateUserItemsByDrop($update)
    {
        if (empty($update)) return;

        $in = [];
        $case = '';
        foreach ($update as $userItemId => $count) {
            $in[] = $userItemId;
            $case .= "WHEN id = {$userItemId} THEN count + {$count} ";
        }
        $in = implode(',', $in);
        $q = "UPDATE items SET count = CASE $case END WHERE id IN({$in})";
        DB::query($q);
    }

    private function insertUserItemsByDrop($userId, $insert)
    {
        if (empty($insert)) return;

        $inserStrings = [];
        foreach ($insert as $itemId => $count) {
            $inserStrings[] = [
                'owner_id' => $userId,
                'item_id' => $itemId,
                'count' => $count,
            ];
        }
        DB::table('items')->insert($inserStrings);
    }
}
