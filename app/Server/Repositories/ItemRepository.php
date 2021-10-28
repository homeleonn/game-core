<?php

namespace App\Server\Repositories;

use Core\Support\Facades\DB;
use Core\Support\Common;
use App\Server\Application;

class ItemRepository
{
    private Application $app;
    private array $items;

    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->items = Common::itemsOnKeys(
            DB::getAll('Select * from allitems'),
            ['item_id'],
            function(&$item) {
                switch ($item->item_type) {
                    case 'gift': $img = 'gifts'; break;
                    case 'fish': $img = 'fishing'; break;
                    case 'trash': $img = 'other'; break;

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
}
