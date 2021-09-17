<?php

namespace App\Repositories;

use App\Server\Loaders\{LocationsLoader, NpcLoader};
use App\Models\{Loc, User};
use App\Application;

class LocRepository extends BaseRepository
{
	protected Application $app;
	public array $locsFds;
	public array $locs = [];
	public array $closestLocs = [];
	public array $npc = [];
	public array $locMonsters = [];

	public function __construct(Application $app)
	{
		parent::__construct($app);
		[$this->locs, $this->closestLocs] = (new LocationsLoader)->load();
		$this->npc = (new NpcLoader)->load();
		$this->spawnNpc();
	}

	public function addUser($user, $to = null)
	{
		[$toLoc, $fd, $userId] = $this->getUserData($user);
		$to = $to ?? $toLoc;

		$this->app->sendToLoc($to, ['addLocUser' => $user->show()]);
		$this->locsFds[$to][$fd] = null;
	}

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

	public function getUserData($user)
	{
		return [$user->getLoc(), $user->getFd(), $user->getId()];
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
				'loc' 			=> $loc, 
				'closestLocs' 	=> $this->closestLocs[$loc->id],
				'locUsers'		=> $this->app->userRepo->getLocUsers($user)
			]]);
		}
	}

	public function chloc(User $user, int $to)
	{
		$from 		= $user->getLoc();
		$fd 		= $user->getFd();

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
		$spawnlist = \DB::getAll('SELECT id, npc_id, loc_id, respawn_delay FROM spawnlist');
		foreach ($spawnlist as $npc) {
			if (!isset($this->locMonsters[$npc->loc_id])) {
				 $this->locMonsters[$npc->loc_id] = [];
			}

			$this->locMonsters[$npc->loc_id][] = $this->npc[$npc->npc_id];
		}
	}

	public function getMonsters($user)
	{
		$this->app->send($user->getFd(), ['locMonsters' => $this->locMonsters[$user->loc] ?? []]);
	}

	public function getNpcById($npcId)
	{
		
	}

	public function getEnemy($user, $enemyId)
	{
		$this->app->send($user->getFd(), ['_enemy' => $this->npc[$enemyId]]);
	}
}