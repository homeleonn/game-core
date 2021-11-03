<?php

namespace App\Server\Repositories;

use App\Server\Loaders\{LocationsLoader, NpcLoader};
use App\Server\Models\{Loc, User};
use App\Server\Application;
use Homeleon\Support\Facades\DB;
use Homeleon\Support\Common;

class LocRepository extends BaseRepository
{
    protected Application $app;
    public array $locsFds;
    public array $locs = [];
    public array $closestLocs = [];
    public array $npcProtoList = [];
    public array $npcByLocation = [];

    public function __construct(Application $app)
    {
        parent::__construct($app);
        [$this->locs, $this->closestLocs] = (new LocationsLoader)->load();
        $this->npcProtoList = (new NpcLoader)->load();
        $this->spawnNpc();
    }

    public function addUser($user, $to = null)
    {
        [$toLoc, $fd, $userId] = $this->getUserData($user);
        $to = $to ?? $toLoc;

        $this->app->sendToLoc($to, ['addLocUser' => $user->locProps()]);
        $this->locsFds[$to][$fd] = null;
    }

    /**
     * Remove user from location
     *
     * @param User $user
     * @return bool
     */
    public function removeUser(User $user)
    {
        [$fromLoc, $fd, $userId] = $this->getUserData($user);//d($this->getUserData($user));

        if (isset($this->locsFds[$fromLoc]) && array_key_exists($fd, $this->locsFds[$fromLoc])) {
            unset($this->locsFds[$fromLoc][$fd]);

            $this->app->sendToLoc($fromLoc, ['leaveLocUser' => ['id' => $user->id]]);

            return true;
        }

        return false;
    }

    public function replaceUserFd(int $userLocation, int $oldFd, int $newFd)
    {
        unset($this->locsFds[$userLocation][$oldFd]);
        $this->locsFds[$userLocation][$newFd] = null;
    }

    public function getUserData($user)
    {
        return $user->getDataForLocation();
    }

    public function getLoc(int $locId)
    {
        return $this->locsFds[$locId] ?? [];
    }

    // current loc data
    public function sendLoc($user)
    {
        if ($loc = $this->locs[$user->loc] ?? null) {
            $this->app->send($user->getFd(), ['loc' => (object)[
                'loc'             => $loc,
                'closestLocs'     => $this->closestLocs[$loc->id],
                'locUsers'        => $this->app->userRepo->getLocUsers($user)
            ]]);
        }
    }

    public function chloc(User $user, int $to)
    {
        $from       = $user->loc;
        $fd         = $user->getFd();

        if (!$this->checkChangeLoc($from, $to)) {
            $this->app->send($fd, ['chloc' => 0]);
            return false;
        }

        if ($this->removeUser($user)) {
            $this->addUser($user, $to);
        }

        return true;
    }

    public function checkChangeLoc(int $from, int $to)
    {
        if (isset($this->locs[$from]) &&
            isset($this->locs[$to]) &&
            array_search($to, $this->locs[$from]->loc_access) !== false)
        {
            return true;
        }
    }

    private function spawnNpc()
    {
        $spawns = DB::getAll('SELECT id, npc_id, loc_id, respawn_delay FROM spawnlist');

        foreach ($spawns as $spawn) {
            if (!isset($this->npcByLocation[$spawn->loc_id])) {
                 $this->npcByLocation[$spawn->loc_id] = [];
            }

            $npc = clone($this->npcProtoList[$spawn->npc_id]);
            $npc->npc_id = $npc->id;
            $npc->id     = $spawn->id;
            $this->npcByLocation[$spawn->loc_id][$spawn->id] = $npc;
        }
    }

    public function getMonsters($user)
    {
        $this->app->send($user->getFd(), ['locMonsters' => Common::arrayPropsOnly($this->npcByLocation[$user->loc] ?? [], [
            'aggr', 'level', 'image', 'id', 'name', 'is_undead', 'fight'
        ])]);
    }

    public function getNpcById($npcId)
    {

    }

    public function getEnemy($user, $enemyId)
    {
        $this->app->send($user->getFd(), ['_enemy' => $this->npcProtoList[$enemyId]]);
    }

    public function attackMonster($user, $monsterId)
    {
        $monster = $this->npcByLocation[$user->loc][$monsterId] ?? null;
        if (!$monster) {
            d("Monster with id '{$monsterId}' doesn't exists in location id '{$user->loc}'");
            return;
        }
        $this->app->send($user->getFd(), ['attackMonster' => 1]);
        $this->app->fightRepo->init($user, $monster);
        $this->informingUsersForAttackedMonster($user->loc, $monsterId);
    }

    private function informingUsersForAttackedMonster($loc, $monsterId)
    {
        $this->app->sendToLoc($loc, ['monsterAttacked' => $monsterId]);
    }
}
