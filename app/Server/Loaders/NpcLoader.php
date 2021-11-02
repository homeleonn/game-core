<?php

namespace App\Server\Loaders;

use Homeleon\Support\Facades\DB;
use Homeleon\Support\Common;
use App\Server\Models\{User, Npc};

class NpcLoader
{
    public function load()
    {
        return Npc::select('id', 'name', 'level', 'curhp', 'maxhp', 'power', 'critical', 'evasion', 'stamina', 'aggr', 'is_undead', 'image')->by('id')->all();
    }
}
