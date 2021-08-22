<?php

namespace App;

use App\Server\Contracts\StoreContract;

class User 
{
	const CAN_TRANSITION_YES = 1;
	const CAN_TRANSITION_NO = 0;
	const TRANSITION_TIMEOUT = 0;

	private $store;
	private $fd;
	private $SID;
	public $id;
	public $name;
	private $room;
	private $transitionTimeout;
	private static $ttl = 1800;

	public function __construct(StoreContract $store, int $fd, string $SID, array $user)
	{
		$this->store 	= $store;
		$this->fd 		= $fd;
		$this->SID 		= $SID;
		$this->id 		= $user['id'];
		$this->name 	= $user['name'];
		$this->room 	= $user['room'];
		$this->transitionTimeout 	= $user['transitionTimeout'];
	}

	public function getId(): int
	{
		return $this->id;
	}

	public function getFd(): int
	{
		return $this->fd;
	}

	public function getName()
	{
		return $this->name;
	}

	public function getSID()
	{
		return $this->SID;
	}

	public function getRoom(): int
	{
		return $this->room;
	}

	public function setRoom(int $room): self
	{
		$this->room = $room;
		$this->transitionTimeout = time() + self::TRANSITION_TIMEOUT;

		return $this;
	}

	public function chroom(int $to, $app)
	{
		if ($this->canTransition()) {
			return $app->send($this->fd, ['transition_timeout' => null]);
		}

		if (!$app->roomRepo->chroom($this, $to)) {
			return;
		}
		
		$this->setRoom($to)->save(); // Need save user ?
		$app->send($this->fd, ['chroom' => static::CAN_TRANSITION_YES]);
		$app->send($this->fd, ['room_users' => $app->userRepo->getAllByRoom($to)]);
		$app->getLocation($this);
	}

	public function save()
	{
		$this->store->set('SID:' . $this->SID, serialize([
			'id' 	=> $this->id,
			'name' 	=> $this->name,
			'room' 	=> $this->room,
			'transitionTimeout' 	=> $this->transitionTimeout,
		]), self::$ttl);
	}

	public function canTransition()
	{
		return $this->transitionTimeout >= time();
	}

	public function __toString()
	{
		return $this->asString();
	}

	public function __debugInfo()
	{
		return [$this->asString()];
	}

	public function asString()
	{
		return "fd:{$this->fd} SID:{$this->SID} id:{$this->id} name:{$this->name} room:{$this->room}";
	}
}