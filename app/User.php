<?php

namespace App;

use App\Server\Contracts\StoreContract;
use DB;

class User 
{
	const CAN_TRANSITION_YES = 1;
	const CAN_TRANSITION_NO = 0;
	const TRANSITION_TIMEOUT = 0;

	private StoreContract $store;
	private int $fd;
	private object $attr;

	public function __construct(StoreContract $store, int $fd, object $user)
	{
		$this->store 	= $store;
		$this->fd 		= $fd;
		$this->attr 	= $user;
		$this->attr->fd = $fd;
	}

	public function setLoc(int $loc): self
	{
		$this->loc = $loc;
		$this->trans_timeout = time() + self::TRANSITION_TIMEOUT;

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
		
		$this->setLoc($to);
		$app->send($this->fd, ['chloc' => static::CAN_TRANSITION_YES]);
		$app->send($this->fd, ['loc_users' => $app->userRepo->getAllByLoc($to)]);
		$app->locRepo->sendLoc($this);
		$this->save(); // Need to save user ?
	}

	public function getBackPack($app)
	{
		$userItems = DB::getAll("Select * from items where owner_id = {$this->id}");

		if (!$userItems) return;

		foreach ($userItems as &$item) {
			$item = (object)array_merge((array)$app->itemRepo->getItemById($item->item_id), (array)$item);
		}

		$app->send($this->getFd(), 
			['backpack' => $userItems]
		);
	}

	public function save()
	{
		// DB::query("UPDATE users SET loc = ".$this->loc." WHERE id = ?i", $this->id);
	}

	public function canTransition()
	{
		return $this->trans_timeout >= time();
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
		return "fd:{$this->fd} id:{$this->id} name:{$this->login} loc:{$this->loc}";
	}

	public function show()
	{
		return (object)[
			'id' 	=> $this->getId(),
			'login' => $this->getLogin(),
			'level' => $this->getLevel(),
		];
	}

	public function __call($method, $args)
	{
		if (preg_match('/^get(.+)/', $method, $matches)) {
			return $this->attr->{lcfirst($matches[1])} ?? null;
		}
	}

	public function __get($key)
	{
		return $this->attr->$key ?? null;
	}

	public function __set($key, $value)
	{
		$this->attr->{$key} = $value;
	}
}