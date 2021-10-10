<?php

namespace App\Server\Models;

use Core\Helpers\Common;
use Core\Contracts\Session\Session;
use DB;

class User 
{
	const CLEAR_EXITERS_TIMEOUT = 10;
	const CAN_TRANSITION_YES = 1;
	const CAN_TRANSITION_NO = 0;
	const TRANSITION_TIMEOUT = 0;

	const ITEM_REMOVE_YES = 1;
	const ITEM_REMOVE_NO = 0;

	private Session $storage;
	private int $fd;
	public object $attr;

	public function __construct(Session $storage, int $fd, object $user)
	{
		$this->storage 	= $storage;
		$this->fd 		= $fd;
		$this->attr 	= (object)Common::toNums($user);
		// $this->attr->fd = $fd;

		
		[$this->min_damage, $this->max_damage] = self::calculateDamage($this->power);
		$this->extra_min_damage = 0;
		$this->extra_max_damage = 0;
	}

	public function isAdmin()
	{
		return $this->access_level == 1;
	}

	public function setLoc(int $loc): self
	{
		$this->loc = $loc;
		$this->trans_timeout = time() + self::TRANSITION_TIMEOUT;

		return $this;
	}

	public function chloc(int $to, $app)
	{
		if (!$this->canTransition()) {
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
		// $userItems = DB::getAll("Select * from items where owner_id = {$this->id}");

		$this->packItems = Common::itemsOnKeys(
			DB::getAll("Select * from items where owner_id = {$this->id}"),
			['id'],
			function(&$item) use ($app) {
				$item = (object)array_merge((array)$app->itemRepo->getItemById($item->item_id), (array)$item);
			}
		);

		if (!$this->packItems) return;

		// foreach ($this->attr->packItems as &$item) {
		// 	$item = (object)array_merge((array)$app->itemRepo->getItemById($item->item_id), (array)$item);
		// }


		$app->send($this->getFd(), 
			['backpack' => $this->packItems]
		);
	}

	private function removeItem($app, $itemId)
	{
		$app->send($this->fd, 
			['itemActionRemove' => 1]
		);

		if ($remove) {
			unset($this->packItems[$itemId]);
			DB::query('DELETE from items where id = ?i', $itemId);
		}
	}

	private function wearItem($app, $itemId)
	{
		$app->send($this->fd, 
			['itemActionWear' => 1]
		);

		// if ($remove) {
			DB::query("UPDATE items SET loc = 'WEARING' where id = ?i", $itemId);
		// }
	}

	public function itemAction($app, $action, $itemId)
	{
		if (!$this->canAction($action) || $this->fight || !$this->itemExists($itemId)) return;

		$this->{$action}($app, $itemId);
	}

	private function canAction($action)
	{
		return method_exists($this, $action);
	}

	private function itemExists($itemId)
	{
		return isset($this->packItems[$itemId]);
	}

	public function save()
	{
		// DB::query("UPDATE users SET loc = ".$this->loc." WHERE id = ?i", $this->id);
	}

	public function canTransition()
	{
		return $this->trans_timeout <= time();
	}

	public function locProps()
	{
		return (object)[
			'id' 	=> $this->getId(),
			'login' => $this->getLogin(),
			'level' => $this->getLevel(),
		];
	}

	public function getAll()
	{
		return $this->attr;
	}

	public function getFd()
	{
		return $this->fd;
	}

	public function setFd($fd)
	{
		$this->fd = $fd;
	}

	public static function calculateDamage($power)
	{
		return [floor($power / 2), $power + ceil($power / 2)];
	}

	public function getDataForLocation()
	{
		return [$this->getLoc(), $this->getFd(), $this->getId()];
	}

	public function clearMarkExit()
	{
		unset($this->exit);
	}

	public function markExit()
	{
		$this->exit = time() + self::CLEAR_EXITERS_TIMEOUT;
	}

	public function isExit()
	{
		return isset($this->exit);
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

	public function __toString()
	{
		return $this->asString();
	}

	public function asString()
	{
		return "fd:{$this->fd} id:{$this->id} name:{$this->login} loc:{$this->loc}";
	}

	public function __debugInfo()
	{
		return [$this->asString()];
	}
}