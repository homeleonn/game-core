<?php

namespace App\Server\Repositories;

use Homeleon\Support\{Obj, Common};
use Homeleon\Support\Facades\DB;
use App\Server\Loaders\NpcLoader;
use App\Server\Models\Npc;
use App\Server\Application;
use stdClass;

class NpcRepository extends BaseRepository
{
    public array $protos = [];
    public array $byLocation = [];
    private array $respawn = [];
    public array $publicProps = ['aggr', 'level', 'image', 'id', 'name', 'is_undead', 'fight'];

    public function __construct(Application $app)
    {
        parent::__construct($app);

        $this->protos = (new NpcLoader)->load();
        $this->initSpawn();
    }

    private function initSpawn()
    {
        $spawns = DB::getAll('SELECT id, npc_id, loc_id, respawn_delay FROM spawnlist');
        foreach ($spawns as $spawn) {
            $this->spawn($spawn);
        }
    }

    public function spawn(array|object $spawn)
    {
        if (!isset($this->byLocation[$spawn->loc_id])) {
            $this->byLocation[$spawn->loc_id] = [];
        }

        $npc = clone($this->protos[$spawn->npc_id]);
        Obj::merge($npc, $spawn);
        $this->byLocation[$spawn->loc_id][$spawn->id] = $npc;

        return $npc;
    }

    public function get($id)
    {
        return $this->protos[$id] ?? null;
    }

    public function all()
    {
        return $this->protos;
    }

    public function getByLoc($loc, $protoId = null)
    {
        return !$protoId ? ($this->byLocation[$loc] ?? null) : ($this->byLocation[$loc][$protoId] ?? null);
    }

    public function kill($npc)
    {
        $spawn = new stdClass;

        foreach (['id', 'npc_id', 'loc_id', 'respawn_delay'] as $key) {
            $spawn->{$key} = $npc->{$key};
        }

        // $spawn->respawn_time = time() + $spawn->respawn_delay;
        $spawn->respawn_time = time() + 20;
        $this->respawn[$spawn->id] = $spawn;

        unset($this->byLocation[$spawn->loc_id][$spawn->id]);

        $this->app->sendToLoc($spawn->loc_id, ['killMonster' => $spawn->id]);
    }

    public function respawn()
    {
        if (empty($this->respawn)) return;

        $time = time();
        foreach ($this->respawn as $key => $spawn) {
            if ($spawn->respawn_time > $time) continue;
            $spawnedNpc = $this->spawn($spawn);
            $this->app->sendToLoc($spawn->loc_id, ['spawnMonster' => Common::propsOnly($spawnedNpc, $this->publicProps)]);
            unset($this->respawn);
        }
    }
}
