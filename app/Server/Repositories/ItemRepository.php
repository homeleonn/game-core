<?php

namespace App\Server\Repositories;

use Closure;
use Homeleon\Support\Facades\DB;
use Homeleon\Support\Common;
use App\Server\Application;
use App\Server\Models\User;

class ItemRepository extends BaseRepository
{
    private array $items;

    public function __construct(Application $app)
    {
        parent::__construct($app);

        $this->items = Common::itemsOnKeys(
            DB::getAll('Select * from allitems'),
            ['item_id'],
            function(&$item) {
                switch ($item->item_type) {
                    case 'gift': $img = 'gifts'; break;
                    case 'fish': $img = 'fishing'; break;
                    case 'trash': $img = 'other'; break;
                    case 'quest': $img = 'quest'; break;

                    default: $img = 'items';
                }

                $item->image = "/img/{$img}/{$item->image}";
            }
        );
    }

    public function getItemById($itemId)
    {
        return $this->items[$itemId] ?? null;
    }

    public function isStackable($itemId)
    {
        return $this->has($itemId) && $this->getItemById($itemId)->stackable;
    }

    public function has($itemId)
    {
        return isset($this->items[$itemId]);
    }

    public function exchangeWithUser(
        User    $user,
        array   $items,
        Closure $cbEach          = null,
        Closure $cbCommit        = null,
        string  $itemIdAlias     = 'item_id',
        bool    $isAdd           = true,
        bool    $needTransaction = true,
    ): void
    {
        if (empty($items)) return;

        $update = $insert = $delete = [];
        if ($isAdd) {
            $dbAction = 'insert';
            $countAction = 'inc';
        } else {
            $dbAction = 'delete';
            $countAction = 'dec';
        }
        foreach ($items as $item) {
            // If exists stackable item then update count else insert new item
            $userItem = $user->getItemByItemId($item[$itemIdAlias]);
            $action = $userItem && $userItem->stackable
                    ? 'update'
                    : $dbAction;
            $countAction($$action, $item[$itemIdAlias], $item['count']);
            if ($cbEach) $cbEach($item);
        }

        // dd($update, $insert);
        if ($needTransaction) DB::beginTransaction();
        $this->updateUserItems($user->id, $update);
        $this->insertUserItems($user->id, $insert);
        $this->deleteUserItems($user->id, $delete);
        if ($cbCommit) $cbCommit();
        if ($needTransaction) DB::commit();

        $user->loadItems();
    }

    private function updateUserItems($userId, $update)
    {
        if (empty($update)) return;

        $in = [];
        $case = '';
        foreach ($update as $userItemId => $count) {
            $in[] = $userItemId;
            $case .= "WHEN item_id = {$userItemId} THEN count + {$count} ";
        }
        $in = implode(',', $in);
        $q = "UPDATE items SET count = CASE $case END WHERE owner_id = {$userId} AND item_id IN({$in})";
        DB::query($q);
    }

    private function insertUserItems($userId, $insert)
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

    private function deleteUserItems($userId, $delete)
    {
        foreach ($delete as $itemId => $count) {
            DB::table('items')
              ->where('owner_id', $userId)
              ->andWhere('item_id', $itemId)
              ->limit($count)
              ->delete();
        }
    }
}
