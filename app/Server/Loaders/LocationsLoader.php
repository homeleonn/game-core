<?php

namespace App\Server\Loaders;

use DB;
use Core\Common;

class LocationsLoader
{
	public function load()
	{
		$locs 			= DB::getAll('Select * from locations');
		// $locsAccess 	= DB::getAll('Select * from locations_access');

		// $locs 			= $this->setLocsById($locs);
		$locsById 			= Common::itemsOnKeys($locs, ['id']);
		// $locsAccess 	= $this->setLocsAccess($locsAccess);
		$locsAccess 	= $this->setLocsAccess1($locs);

		// $locs 			= $this->setClosestLocs($locs, $locsAccess);

		return [$locsById, $locsAccess];
		
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
	public function setLocsAccess($rawLocsAccess)
	{
		$locsAccess = [];

		foreach ($rawLocsAccess as $access) {
			if (!isset($locsAccess[$access->loc_id])) $locsAccess[$access->loc_id] = [];
			$locsAccess[$access->loc_id][] = $access->access_loc_id;
		}

		return $locsAccess;
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