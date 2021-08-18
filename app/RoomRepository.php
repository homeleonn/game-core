<?php

namespace App;

class RoomRepository
{
	private $app;

	public $roomsFds;
	private $roomsAvailable = [
		1 => [2, 3, 4, 5, 6, 7],
		2 => [1],
		3 => [1],
		4 => [1],
		5 => [1],
		6 => [1],
		7 => [1],
	];

	public function __construct(Application $app)
	{
		$this->app = $app;
	}

	public function add($user, $to = null)
	{
		$toRoom 	= $to ?: $user->getRoom();
		$fd 		= $user->getFd();
		$userId		= $user->getId();

		$this->app->sendToRoom($toRoom, ['room_add' => $user]);
		$this->roomsFds[$toRoom][$fd] = null;
	}

	public function remove(User $user)
	{
		$fromRoom 	= $user->getRoom();
		$fd 		= $user->getFd();
		$userId		= $user->getId();

		if (isset($this->roomsFds[$fromRoom]) && array_key_exists($fd, $this->roomsFds[$fromRoom])) {
			unset($this->roomsFds[$fromRoom][$fd]);

			$this->app->sendToRoom($fromRoom, ['room_leave' => ['id' => $user->getId()]]);

			return true;
		}

		return false;
	}

	public function getRoom(int $roomId)
	{
		return $this->roomsFds[$roomId] ?? [];
	}

	public function chroom(User $user, int $to)
	{
		$from 		= $user->getRoom();
		$fd 		= $user->getFd();

		if (!$this->checkChangeRoom($from, $to)) {
			$this->app->send($fd, ['chroom' => 0]);
			return false;
		}

		if ($this->remove($user)) {
			$this->add($user, $to);
		}

		return true;
	}

	public function checkChangeRoom(int $from, int $to)
	{
		if (isset($this->roomsAvailable[$from]) && 
			isset($this->roomsAvailable[$to]) && 
			array_search($to, $this->roomsAvailable[$from]) !== false) 
		{
			return true;
		}
	}
}