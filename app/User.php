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

	public function setLoc(int $loc): self
	{
		$this->loc = $loc;
		$this->transitionTimeout = time() + self::TRANSITION_TIMEOUT;

		return $this;
	}

	public function chloc(int $to, $app)
	{
		if ($this->canTransition()) {
			return $app->send($this->fd, ['transition_timeout' => null]);
		}

		if (!$app->locRepo->chloc($this, $to)) {
			return;
		}
		
		$this->setLoc($to)->save(); // Need save user ?
		$app->send($this->fd, ['chloc' => static::CAN_TRANSITION_YES]);
		$app->send($this->fd, ['loc_users' => $app->userRepo->getAllByLoc($to)]);
		$app->getLoc($this);
	}

	public function save()
	{

		DB::query("UPDATE users SET loc = ".$this->get('loc')." WHERE id = ?i", $this->get('id'));
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
		return "fd:{$this->fd} id:{$this->id} name:{$this->name} loc:{$this->loc}";
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