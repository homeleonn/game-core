<?php

namespace App\Server\Repositories;

use Homeleon\Support\Facades\DB;
use Homeleon\Support\{Common, Obj};
use App\Server\Application;
use App\Server\Models\Quest\UserQuest;
use App\Server\Models\User;

class QuestRepository extends BaseRepository
{
    private array $quests;
    // hard code list quest items for quests
    // item id => quest ids
    public array $questItems = [
        29 => [
            'quest_id' => 1,
            'count' => 5
        ],
    ];

    public function __construct(Application $app)
    {
        parent::__construct($app);

        $quests = DB::getAll('SELECT * from quests');
        $this->quests = Common::itemsOnKeys1($quests, ['id', 'npc_id'], fn ($quest) => $quest->data = json_decode($quest->data, true));
    }

    public function isQuestItem($item_id)
    {
        return isset($this->questItems[$itemId]);
    }

    private function showQuests($user, int $npcId)
    {
        if (!repo('loc')->checkChangeLoc($user->loc, $npcId) || !isset($this->quests['npc_id'][$npcId])) {
            return error($user->getFd(), 'Поговорить не удалось');
        }

        $npcQuests = array_map(
            fn ($quest) => clone($quest),
            array_filter(
                $this->quests['npc_id'][$npcId],
                fn ($quest) => $quest->level <= $user->level
            )
        );
        $npcQuests = Common::itemsOnKeys($npcQuests, ['id']);
        $userQuests = DB::getInd('quest_id',
            "SELECT u.* FROM user_quests u
            LEFT JOIN
                quests q ON u.quest_id = q.id
            WHERE
                q.npc_id = {$npcId} AND u.user_id = {$user->id}"
        );

        if ($userQuests) {
            $userQuests = array_map(fn ($quest) => new UserQuest((array)$quest), $userQuests);
        }
        $npcQuests = $this->removeCompletedQuests($userQuests, $npcQuests);

        return [$npcQuests, $userQuests];
    }

    public function showQuest($user, int $npcId, int $questId)
    {
        if (!$quests = $this->getQuests($user, $npcId, $questId)) return;
        [$npcQuests, $userQuests, $showQuest] = $quests;

        $showQuest = $this->resolveQuest($user, $showQuest, $userQuests, $questId);
    }

    public function talkToNpc($user, int $npcId)
    {
        [$npcQuests] = $this->showQuests($user, $npcId);
        array_walk($npcQuests, function ($quest) { Obj::only($quest, ['name', 'id']); });

        send($user->getFd(), ['talkToNpc' => repo('loc')->get($npcId)]);
        send($user->getFd(), ['quests' => $npcQuests]);
    }

    public function answer($user, int $npcId, int $questId, $answerId)
    {
        if (!$quests = $this->getQuests($user, $npcId, $questId)) return;
        [$npcQuests, $userQuests, $showQuest] = $quests;

        $step = $userQuests[$questId]->step ?? 0;
        if (!$this->checkCorrectAnswer($showQuest, $answerId, $step)) return error($user->getFd(), 'Некорректный ответ');

        if ($answerId == 'abort') {
            if (isset($userQuests[$questId])) {
                $userQuests[$questId]->delete();
            }
            $showQuest->data = $showQuest->data['steps'][$answerId];
            return $user->send(['showQuest' => $showQuest]);
        }

        if (!isset($userQuests[$questId])) {
            $userQuests[$questId] = new UserQuest();
            $userQuests[$questId]->user_id = $user->id;
            $userQuests[$questId]->quest_id = $questId;
        }

        // User has accepted the quest
        if (!isset($showQuest->data['steps'][$answerId]['answers'])) {
            $userQuests[$questId]->step = 0;
        } else { // User has continued quest dialog
            $userQuests[$questId]->step = $answerId;
        }

        $userQuests[$questId]->save();
        $showQuest->data = $showQuest->data['steps'][$answerId];
        $user->send(['showQuest' => $showQuest]);

        // $showQuest = $this->resolveQuest($user, $showQuest, $userQuests, $questId);
    }

    /**
     * Check step of user quest:
     *     being done
     *     has done
     *     on step of dialog
     *
     * @param  User   $user
     * @param  object $showQuest  current dialog quest
     * @param  array  $userQuests
     * @param  int    $questId
     * @return void
     */
    private function resolveQuest(User $user, object $showQuest, array $userQuests, int $questId): void
    {
        if (isset($userQuests[$questId])) {
            $action = '';
            // Quest had been taken, check if done
            if (!$userQuests[$questId]->step) {
                $action = $this->checkDone($user, $showQuest) ? 'done' : 'do';
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

        if (isset($showQuest->data['reward'])) {
            $showQuest->data['reward'] = $this->rewardItems($showQuest->data['reward']);
        }

        $user->send(['showQuest' => $showQuest]);

        // return $showQuest;
    }

    private function getQuests($user, $npcId, $questId)
    {
        [$npcQuests, $userQuests] = $this->showQuests($user, $npcId);

        if (!$showQuest = $this->get($user, $npcQuests, $questId)) return;

        return [$npcQuests, $userQuests, $showQuest];
    }

    public function takeReward($user, $npcId, $questId)
    {
        if (!$quests = $this->getQuests($user, $npcId, $questId)) return;
        [,,$showQuest] = $quests;

        if (!$this->checkDone($user, $showQuest)) return;

        $rewardMessage = [];
        repo('item')->addToUser(
            $user,
            $showQuest->data['done']['reward'],
            function ($drop) use (&$rewardMessage) {
                $rewardMessage[] = [
                    'item_id'   => $drop['id'],
                    'name'      => repo('item')->getItemById($drop['id'])->name,
                    'count'     => $drop['count']
                ];
            },
            function () use ($user, $questId) {
                DB::table('user_quests')
                  ->where('user_id', $user->id)
                  ->andWhere('quest_id', $questId)
                  ->update(['completed' => 1]);
            },
            'id');

        if (!empty($rewardMessage)) {
            $user->send(['questReward' => $rewardMessage]);
        }
    }

    private function checkCorrectAnswer($showQuest, $answer, $step)
    {
        return isset($showQuest->data['steps'][$step]["answers"][$answer]);
    }

    private function rewardItems(array $rewards): array
    {
        // if (!isset($showQuest->data['reward'])) return [];

        $rewardItems = [];
        foreach ($rewards as $reward) {
            $item = Common::propsOnly(repo('item')->getItemById($reward['id']), ['name', 'image'], true);
            $item->count = $reward['count'] ?? 1;
            $rewardItems[] = $item;
        }

        return $rewardItems;
    }

    private function get($user, $npcQuests, $questId)
    {
        if (!isset($npcQuests[$questId])) {
            error($user->getFd(), 'Такого квеста нет');
            return false;
        }

        return clone($npcQuests[$questId]);
    }

    private function checkDone($user, $showQuest)
    {
        $cond = $showQuest->data['condition']['done'];
        return $user->countOfItems($cond['id']) >= $cond['count'];
    }

    private function removeCompletedQuests($userQuests, $npcQuests)
    {
        foreach ($userQuests as $userQuest) {
            if ($userQuest->completed) unset($npcQuests[$userQuest->quest_id]);
        }

        return $npcQuests;
    }
}
