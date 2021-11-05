<?php

namespace App\Server\Repositories;

use Homeleon\Support\Facades\DB;
use Homeleon\Support\Common;
use App\Server\Application;
use App\Server\Models\Quest\UserQuest;

class QuestRepository extends BaseRepository
{
    private array $quests;

    public function __construct(Application $app)
    {
        parent::__construct($app);

        $quests = DB::getAll('SELECT * from quests');
        $this->quests = Common::itemsOnKeys1($quests, ['id', 'npc_id'], fn ($quest) => $quest->data = json_decode($quest->data, true));
    }

    private function showQuests($user, int $npcId)
    {
        if (!repo('loc')->checkChangeLoc($user->loc, $npcId) || !isset($this->quests['npc_id'][$npcId])) {
            return error($user->getFd(), 'Поговорить не удалось');
        }

        $npcQuests = array_filter($this->quests['npc_id'][$npcId], fn ($quest) => $quest->level <= $user->level);
        $npcQuests = Common::itemsOnKeys($npcQuests, ['id']);
        $userQuests = DB::getInd('quest_id',
            "SELECT u.* FROM user_quests u
            LEFT JOIN
                quests q ON u.quest_id = q.id
            WHERE
                q.npc_id = {$npcId} AND u.user_id = {$user->id}"
        );

        if ($userQuests) $userQuests = array_map(fn ($quest) => new UserQuest((array)$quest));
        $npcQuests = $this->removeCompletedQuests($userQuests, $npcQuests);

        return [$npcQuests, $userQuests];
    }

    public function showQuest($user, int $npcId, int $questId)
    {
        [$npcQuests, $userQuests] = $this->showQuests($user, $npcId);

        if (!$showQuest = $this->get($user, $npcQuests, $questId)) return;

        $showQuest = $this->resolveQuest($user, $showQuest, $userQuests, $questId);
    }

    public function talkToNpc($user, int $npcId)
    {
        [$npcQuests] = $this->showQuests($user, $npcId);

        send($user->getFd(), ['talkToNpc' => repo('loc')->get($npcId)]);
        send($user->getFd(), ['quests' => $npcQuests]);
    }

    public function answer($user, int $npcId, int $questId, $answerId)
    {
        [$npcQuests, $userQuests] = $this->showQuests($user, $npcId);

        if (!$showQuest = $this->get($user, $npcQuests, $questId)) return;
        $step = $userQuests[$questId]->step ?? 0;
        if (!$this->checkCorrectAnswer($showQuest, $answerId, $step)) return error($user->getFd(), 'Некорректный ответ');

        if ($step == 'abort') return;

        if (!isset($userQuests[$questId])) {
            $userQuests[$questId] = new UserQuest();
            $userQuests[$questId]->user_id = $user->id;
            $userQuests[$questId]->quest_id = $questId;
            $userQuests[$questId]->step = $step;
        } else {
            $userQuests[$questId]->step = $step;
        }

        $userQuests[$questId]->save();

        $showQuest = $this->resolveQuest($user, $showQuest, $userQuests, $questId);
    }

    private function resolveQuest($user, $showQuest, $userQuests, $questId)
    {
        if (isset($userQuests[$questId])) {
            // Quest had been taken, check if done
            if (!$userQuests[$questId]->step) {
                $action = $this->checkDone($user, $showQuest->data['condition']['done']) ? 'done' : 'do';
                $showQuest->data = $showQuest->data[$action];
            } else {
                $userQuestStep = $userQuests[$questId]->step;
            }
        } else {
            $userQuestStep = 0;
        }

        if (isset($userQuestStep)) {
            $showQuest->data = $showQuest->data['steps'][$userQuestStep];
        }

        $user->send(['showQuest' => $showQuest]);

        // return $showQuest;
    }

    private function checkCorrectAnswer($showQuest, $answer, $step)
    {
        return isset($showQuest->data['steps'][$step]["answers"][$answer]);
    }

    private function get($user, $npcQuests, $questId)
    {
        if (!isset($npcQuests[$questId])) {
            error($user->getFd(), 'Такого квеста нет');
            return false;
        }

        return clone($npcQuests[$questId]);
    }

    private function checkDone($user, $condition)
    {
        $userQuestItemsCount = 0;
        foreach ($user->getItems() as $item) {
            if ($item->item_id == $condition['id']) {
                $userQuestItemsCount++;
            }

            if ($userQuestItemsCount >= $condition['count']) {
                return true;
            }
        }

        return false;
    }

    private function removeCompletedQuests($userQuests, $npcQuests)
    {
        foreach ($userQuests as $userQuest) {
            if ($userQuest->completed) unset($npcQuests[$userQuest->quest_id]);
        }

        return $npcQuests;
    }
}
