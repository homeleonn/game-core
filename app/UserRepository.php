<?php

namespace App;

class UserRepository
{
	private $redis;
	private $app;
	public $users;
	public $usersIds;

	public function __construct($redis, $app)
	{
		$this->redis = $redis;
		$this->app 	 = $app;
	}

	public function add(int $fd, $SID, $user)
	{
		$this->users[$fd] 	 = new User($this->redis, $fd, $SID, $user);
		$this->usersIds[$user['id']] = $fd;

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
		if (isset($this->usersIds[$id]) && isset($this->users[$this->usersIds[$id]])) {
			return $this->users[$this->usersIds[$id]];
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

	public function init(string $userSID)
	{
		if ($user = $this->getUser($userSID)) {
			return $user;
		}

	}

	private function getUser($userSID)
	{
		$user = $this->redis->get('SID:' . $userSID);
		
		return $user ? unserialize($user) : false;
	}

	public function getAll()
	{
		return $this->users;
	}

	public function getAllByRoom($room)
	{
		$users = [];
		
		foreach ($this->app->roomRepo->getRoom($room) as $fd => $dummy) {
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
		return $this->usersIds;
	}

	public function getSIDs()
	{
		return array_reduce($this->users, function($carry, $item) {
			$carry[] = $item->SID;
			return $carry;
		}, []);
	}
}