<?php

namespace App\Server\Loaders;

use DB;
use Core\Helpers\Common;

class LocationsLoader
{
	public function load()
	{
		$locs = DB::getAll('Select * from locations');
		
		$locs = Common::itemsOnKeys($locs, ['id'], function($loc) {
			array_map(function($key) use ($loc) {
				$loc->{$key} = json_decode($loc->{$key});
			}, ['loc_coords', 'loc_access']);
		});

		$this->setClosestLocs1($locs);

		return $locs;
		
	}

	public function setLocsById($rawLocs)
	{
		$locs = [];

		foreach ($rawLocs as $loc) {
			$loc->locs_coords = json_decode($loc->locs_coords);
			$locs[$loc->id] = $loc;
		}

		return $locs;
	}
	
	// collect array access locs by id
	public function setClosestLocs1($locs)
	{
		foreach ($locs as $loc) {
			foreach ($loc->loc_access as $locId) {
				if (!isset($loc->closest_locs[$locs[$locId]->type])) {
					// $loc->closest_locs = [];
					$loc->closest_locs[$locs[$locId]->type] = [];
				}

				$loc->closest_locs[$locs[$locId]->type][$locId] = $locs[$locId]->name;
			}
		}
		
		// $locsAccess = [];

		// foreach ($rawLocsAccess as $access) {
		// 	if (!isset($locsAccess[$access->loc_id])) $locsAccess[$access->loc_id] = [];
		// 	$locsAccess[$access->loc_id][] = $access->access_loc_id;
		// }

		// return $locsAccess;
	}

	// collect array access locs by id
	public function setLocsAccess1($rawLocsAccess)
	{
		$locsAccess = [];

		foreach ($rawLocsAccess as $access) {
			if (!isset($locsAccess[$access->loc_id])) $locsAccess[$access->loc_id] = [];
			$locsAccess[$access->loc_id][] = $access->access_loc_id;
		}

		return $locsAccess;
	}

	// Bind closest locs and sort them by id
	public function setClosestLocs($locs, $locsAccess)
	{
		foreach ($locs as $id => $loc) {
			foreach ($locsAccess[$id] as $locId) {
				if (!isset($locs[$id]->closest_locs[$locs[$locId]->type])) {
					$locs[$id]->closest_locs[$locs[$locId]->type] = [];
				}

				$locs[$id]->closest_locs[$locs[$locId]->type][$locs[$locId]->id] = $locs[$locId]->name;
			}
		}

		return $locs;
	}
}