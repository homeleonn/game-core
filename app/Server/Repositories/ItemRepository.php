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

    public function addToUser(
        User $user,
        array $items,
        Closure $cbEach = null,
        Closure $cbCommit = null,
        string $itemIdAlias = 'item_id'
    ): void
    {
        if (empty($items)) return;

        $update = $insert = [];
        foreach ($items as $item) {
            // If exists stackable item then update count else insert new item
            $userItem = $user->getItemByItemId($item[$itemIdAlias]);
            $action = $userItem && $userItem->stackable
                    ? 'update'
                    : 'insert';
            inc($$action, $item[$itemIdAlias], $item['count']);
            if ($cbEach) $cbEach($item);
        }

        dd($update, $insert);
        DB::beginTransaction();
        $this->updateUserItemsByDrop($update);
        $this->insertUserItemsByDrop($user->id, $insert);
        if ($cbCommit) $cbCommit();
        DB::commit();

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
