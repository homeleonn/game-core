<?php

namespace App;

use App\Server\Contracts\StoreContract;

class User 
{
	const CAN_TRANSITION_YES = 1;
	const CAN_TRANSITION_NO = 0;
	const TRANSITION_TIMEOUT = 0;

	private StoreContract$store;
	private int $fd;
	private array $attr = [];
	private static int $ttl = 1800;

	public function __construct(StoreContract $store, int $fd, array $user)
	{
		$this->store 	= $store;
		$this->fd 		= $fd;
		$this->attr 	= $user;
		$this->attr->fd = $fd;
	}

	public function setRoom(int $location): self
	{
		$this->location = $location;
		$this->transitionTimeout = time() + self::TRANSITION_TIMEOUT;

		return $this;
	}

	public function chloc(int $to, $app)
	{
		if ($this->canTransition()) {
			return $app->send($this->fd, ['transition_timeout' => null]);
		}

		if (!$app->roomRepo->chloc($this, $to)) {
			return;
		}
		
		$this->setRoom($to)->save(); // Need save user ?
		$app->send($this->fd, ['chloc' => static::CAN_TRANSITION_YES]);
		$app->send($this->fd, ['room_users' => $app->userRepo->getAllByRoom($to)]);
		$app->getLocation($this);
	}

	public function save()
	{

		DB::query("UPDATE users SET location = ".$this->get('location')." WHERE id = ?i", $this->get('id'));
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
		return "fd:{$this->fd} id:{$this->id} name:{$this->name} room:{$this->room}";
	}

	public function __call($method, $args)
	{
		if (preg_match('/^get(.+)/', $method, $matches)) {
			return $this->attr->{strtolower($matches[1])} ?? null;
		}
	}

	private function get($key)
	{
		return $this->attr->$key;
	}
}