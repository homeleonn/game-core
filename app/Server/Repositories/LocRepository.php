<?php

namespace App\Server\Repositories;

use App\Server\Exceptions\MonsterNotExists;
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

    public function __construct(Application $app)
    {
        parent::__construct($app);
        [$this->locs, $this->closestLocs] = (new LocationsLoader)->load();
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
                'locUsers'        => repo('user')->getLocUsers($user)
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

    public function getMonsters($user)
    {
        $this->app->send(
            $user->getFd(),
            ['locMonsters' => Common::arrayPropsOnly(repo('npc')->getByLoc($user->loc) ?? [], repo('npc')->publicProps)]
        );
    }

    public function getNpcById($npcId)
    {

    }

    public function getEnemy($user, $enemyId)
    {
        $this->app->send($user->getFd(), ['_enemy' => repo('npc')->get($enemyId)]);
    }

    public function attackMonster($user, $monsterId)
    {
        if ($user->percentOfHp() < 33) {
            return $user->send(['error' => 'Hit points are too few']);
        }

        if (!$monster = repo('npc')->getByLoc($user->loc, $monsterId)) {
            // throw new MonsterNotExists("Monster with id '{$monsterId}' doesn't exists in location id '{$user->loc}'");
            $user->send(['error' => "Monster with id '{$monsterId}' doesn't exists in location id '{$user->loc}'"]);
        }

        $isNewFight = repo('fight')->init($user, $monster);
        $this->app->send($user->getFd(), ['attackMonster' => 1]);

        if ($isNewFight) {
            $this->app->sendToLoc($user->loc, ['monsterAttacked' => $monsterId]);
        }
        // $this->informingUsersForAttackedMonster($user->loc, $monsterId);
    }

    private function informingUsersForAttackedMonster($loc, $monsterId)
    {
        $this->app->sendToLoc($loc, ['monsterAttacked' => $monsterId]);
    }
}
