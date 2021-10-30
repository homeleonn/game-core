<?php

namespace App\Server\Repositories;

use App\Server\Loaders\{LocationsLoader, NpcLoader};
use App\Server\Models\{Loc, User};
use App\Server\Application;
use Homeleon\Support\Facades\DB;

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

            $this->app->sendToLoc($fromLoc, ['leaveLocUser' => ['id' => $user->getId()]]);

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
        if ($loc = $this->locs[$user->getLoc()] ?? null) {
            $this->app->send($user->getFd(), ['loc' => (object)[
                'loc'             => $loc,
                'closestLocs'     => $this->closestLocs[$loc->id],
                'locUsers'        => $this->app->userRepo->getLocUsers($user)
            ]]);
        }
    }

    public function chloc(User $user, int $to)
    {
        $from     = $user->getLoc();
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
        $spawnlist = DB::getAll('SELECT id, npc_id, loc_id, respawn_delay FROM spawnlist');
        foreach ($spawnlist as $npcProtoList) {
            if (!isset($this->npcByLocation[$npcProtoList->loc_id])) {
                 $this->npcByLocation[$npcProtoList->loc_id] = [];
            }

            $this->npcByLocation[$npcProtoList->loc_id][$npcProtoList->id] = $this->npcProtoList[$npcProtoList->npc_id];
        }
    }

    public function getMonsters($user)
    {
        $this->app->send($user->getFd(), ['locMonsters' => $this->npcByLocation[$user->loc] ?? []]);
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
        if (!isset($this->npcByLocation[$user->loc][$monsterId])) {
            d("Monster with id '{$monsterId}' doesn't exists in location id '{$user->loc}'");
            return;
        }
        $this->app->fightRepo->init($user, $this->npcByLocation[$user->loc][$monsterId]);
    }
}
