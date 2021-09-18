<?php

namespace App\Server\Loaders;

use DB;
use Core\Helpers\Common;
use App\Models\User;

class NpcLoader
{
	public function load()
	{
		$npc = DB::getAll('SELECT id, name, level, curhp, maxhp, power, critical, evasion, stamina, aggr, is_undead, image FROM npc');
		$npc = Common::itemsOnKeys($npc, ['id'], function($npc) {
			[$npc->min_damage, $npc->max_damage] = User::calculateDamage($npc->power);
		});

		return $npc;
	}

}