<?php

namespace App\Server\Repositories;

use Homeleon\Support\Facades\DB;
use Homeleon\Support\Common;
use App\Server\Application;

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
