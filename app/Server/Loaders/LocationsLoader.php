<?php

namespace App\Server\Loaders;

use Homeleon\Support\Facades\DB;
use Homeleon\Support\Common;
use App\Server\Models\Loc;

class LocationsLoader
{
    public function load()
    {
        $locs = Loc::select('id', 'name', 'type', 'image', 'loc_coords', 'loc_access')
                    ->by('id')
                    ->all();

        Common::exportJsonFields($locs, ['loc_coords', 'loc_access']);

        $closestLocs = $this->setClosestLocs($locs);

        return [$locs, $closestLocs];
    }

    // collect array access locs by id
    public function setClosestLocs($locs)
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
