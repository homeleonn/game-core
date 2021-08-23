<?php

namespace App;

use DB;

class UserRepository
{
	private $store;
	private $app;
	public $users;
	public $userIds;

	public function __construct($app)
	{
		$this->app 	 = $app;
		$this->store = $app->store;
	}

	public function add(int $fd, $user)
	{
		$this->users[$fd] 	 = new User($this->store, $fd, $user);
		$this->userIds[$user->id] = $fd;

		return $this->users[$fd];
	}

	public function findByFd(int $fd)
	{
		if (isset($this->users[$fd])) {
			return $this->users[$fd];
		}

		$this->app->disconnectUndefinedUser($fd);
		// $this->app->removeFromApp($fd);
	}

	public function findById(int $id)
	{
		// По айди юзер может быть, но по айди соединения нет
		if (isset($this->userIds[$id]) && isset($this->users[$this->userIds[$id]])) {
			return $this->users[$this->userIds[$id]];
		}
	}

	public function findByFdAndRemove(int $fd)
	{
		if ($user = $this->findByFd($fd)) {
			unset($this->users[$fd]);

			return $user;
		}
	}

	public function remove($user)
	{
		unset($this->users[$user->getFd()]);
	}

	public function init(string $userId)
	{
		return $this->getUser($userId);
	}

	private function getUser($userId)
	{
		return DB::getRow('SELECT id, login, loc, transition_time_left FROM users WHERE id = ?i', $userId);
	}

	public function getAll()
	{
		return $this->users;
	}

	public function getAllByLoc($loc)
	{
		$users = [];
		
		foreach ($this->app->locRepo->getLoc($loc) as $fd => $dummy) {
			if ($this->has($fd)) {
				$users[] = $this->users[$fd];
			}
		}

		return $users;
	}

	public function has($fd)
	{
		return isset($this->users[$fd]);
	}

	public function getIds()
	{
		return $this->userIds;
	}

	public function getSIDs()
	{
		return array_reduce($this->users, function($carry, $item) {
			$carry[] = $item->SID;
			return $carry;
		}, []);
	}
}