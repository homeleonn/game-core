<?php

namespace App\Server\Loaders;

use DB;
use Core\Helpers\Common;

class NpcLoader
{
	public function load()
	{
		$npc = DB::getAll('SELECT id, name, level, curhp, maxhp, power, critical, evasion, stamina, aggr, is_undead, image FROM npc');
		$npc = Common::itemsOnKeys($npc, ['id']);

		return $npc;
	}

}