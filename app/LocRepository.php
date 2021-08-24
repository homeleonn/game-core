<?php

namespace App;

use App\Server\Loaders\LocationsLoader;

class LocRepository
{
	private Application $app;
	public array $locsFds;
	public array $locs = [];

	public function __construct(Application $app)
	{
		$this->app = $app;
		$this->locs = (new LocationsLoader)->load();
	}

	public function addUser($user, $to = null)
	{
		[$toLoc, $fd, $userId] = $this->getUserData($user);
		$to = $to ?? $toLoc;

		$this->app->sendToLoc($to, ['loc_add' => $user->show()]);
		$this->locsFds[$to][$fd] = null;
	}

	public function removeUser(User $user)
	{
		[$fromLoc, $fd, $userId] = $this->getUserData($user);//d($this->getUserData($user));

		if (isset($this->locsFds[$fromLoc]) && array_key_exists($fd, $this->locsFds[$fromLoc])) {
			unset($this->locsFds[$fromLoc][$fd]);

			$this->app->sendToLoc($fromLoc, ['loc_leave' => ['id' => $user->getId()]]);

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
			$this->app->send($user->getFd(), ['loc' => $loc]);
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
}