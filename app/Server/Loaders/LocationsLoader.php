<?php

namespace App\Server\Loaders;

use Core\Support\Facades\DB;
use Core\Support\Common;
use App\Server\Application;

class LocationsLoader
{
    public function load()
    {
        $locs = DB::getAll('Select id, name, type, image, loc_coords, loc_access from locations');

        $locs = Common::itemsOnKeys($locs, ['id'], function($loc) {
            array_map(function($key) use ($loc) {
                $loc->{$key} = json_decode($loc->{$key});
            }, ['loc_coords', 'loc_access']);
        });

        $closestLocs = $this->setClosestLocs1($locs);

        return [$locs, $closestLocs];
    }

    // collect array access locs by id
    public function setClosestLocs1($locs)
    {
        $closestLocs = [];

        foreach ($locs as $loc) {
            $closestLocs[$loc->id] = [];

            foreach ($loc->loc_access as $locId) {
                if (!isset($closestLocs[$loc->id][$locs[$locId]->type])) {
                    $closestLocs[$loc->id][$locs[$locId]->type] = [];
                }

                $closestLocs[$loc->id][$locs[$locId]->type][$locId] = $locs[$locId]->name;
            }
        }

        return $closestLocs;
    }

}
