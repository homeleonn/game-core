<?php

namespace App\Server\Repositories;

use Homeleon\Support\Facades\DB;
use Homeleon\Support\Common;

class DropRepository extends BaseRepository
{
    private array $drops = [];

    public function __construct($app)
    {
        parent::__construct($app);

        $this->drops = Common::itemsOnKeys1(
            DB::getAll('Select * from drops'),
            ['npc_id', 'item_id']
        );
    }

    public function getByNpc($npcId)
    {
        return $this->drops['npc_id'][$npcId] ?? null;
    }

    public function getByItemId($itemId)
    {
        return $this->drops['item_id'][$itemId];
    }
}
