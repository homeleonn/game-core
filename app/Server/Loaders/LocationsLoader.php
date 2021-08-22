<?php

namespace App\Server\Loaders;

use DB;

class LocationsLoader
{
	public function load()
	{
		$locations 			= DB::getAll('Select * from locations');
		$locationsAccess 	= DB::getAll('Select * from locations_access');

		$locations 			= $this->setLocationsById($locations);
		$locationsAccess 	= $this->setLocationsAccess($locationsAccess);

		$locations 			= $this->setClosestLocations($locations, $locationsAccess);

		return [$locations, $locationsAccess];
		
	}

	public function setLocationsById($rawLocations)
	{
		$locations = [];

		foreach ($rawLocations as $location) {
			$location->locations_coords = json_decode($location->locations_coords);
			$locations[$location->id] = $location;
		}

		return $locations;
	}
	
	// collect array access locations by id
	public function setLocationsAccess($rawLocationsAccess)
	{
		$locationsAccess = [];

		foreach ($rawLocationsAccess as $access) {
			if (!isset($locationsAccess[$access->loc_id])) $locationsAccess[$access->loc_id] = [];
			$locationsAccess[$access->loc_id][] = $access->access_loc_id;
		}

		return $locationsAccess;
	}

	// Bind closest locations and sort them by id
	public function setClosestLocations($locations, $locationsAccess)
	{
		foreach ($this->locations as $id => $location) {
			foreach ($locationsAccess[$id] as $locationId) {
				if (!isset($locations[$id]->closest_locations[$locations[$locationId]->type])) {
					$locations[$id]->closest_locations[$locations[$locationId]->type] = [];
				}

				$locations[$id]->closest_locations[$locations[$locationId]->type][$locations[$locationId]->id] = $locations[$locationId]->name;
			}
		}
	}
}