<?php

namespace App;

use App\Server\Loaders\LocLoader;

class LocRepository
{
	private $app;

	public $locsFds;
	private $locsAvailable = [
		1 => [2, 3, 4, 5, 6, 7],
		2 => [1],
		3 => [1],
		4 => [1],
		5 => [1],
		6 => [1],
		7 => [1],
	];

	public array $locs = [];
	public array $locsAccess = [];

	public function __construct(Application $app)
	{
		$this->app = $app;
		[$this->locs, $this->locsAccess] = (new LocationsLoader)->load();
	}

	public function add($user, $to = null)
	{
		$toLoc 	= $to ?: $user->getLoc();
		$fd 		= $user->getFd();
		$userId		= $user->getId();

		$this->app->sendToLoc($toLoc, ['loc_add' => $user]);
		$this->locsFds[$toLoc][$fd] = null;
	}

	public function remove(User $user)
	{
		$fromLoc 	= $user->getLoc();
		$fd 		= $user->getFd();
		$userId		= $user->getId();

		if (isset($this->locsFds[$fromLoc]) && array_key_exists($fd, $this->locsFds[$fromLoc])) {
			unset($this->locsFds[$fromLoc][$fd]);

			$this->app->sendToLoc($fromLoc, ['loc_leave' => ['id' => $user->getId()]]);

			return true;
		}

		return false;
	}

	public function getLoc(int $locId)
	{
		return $this->locsFds[$locId] ?? [];
	}

	public function chloc(User $user, int $to)
	{
		$from 		= $user->getLoc();
		$fd 		= $user->getFd();

		if (!$this->checkChangeLoc($from, $to)) {
			$this->app->send($fd, ['chloc' => 0]);
			return false;
		}

		if ($this->remove($user)) {
			$this->add($user, $to);
		}

		return true;
	}

	public function checkChangeLoc(int $from, int $to)
	{
		if (isset($this->locsAvailable[$from]) && 
			isset($this->locsAvailable[$to]) && 
			array_search($to, $this->locsAvailable[$from]) !== false) 
		{
			return true;
		}
	}
}