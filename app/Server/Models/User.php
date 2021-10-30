<?php

namespace App\Server\Models;

use Homeleon\Support\Common;
use Redis;
use Homeleon\Support\Facades\DB;

class User
{
    const CLEAR_EXITERS_TIMEOUT = 10;
    const CAN_TRANSITION_YES = 1;
    const CAN_TRANSITION_NO = 0;
    const TRANSITION_TIMEOUT = 20;

    const ITEM_REMOVE_YES = 1;
    const ITEM_REMOVE_NO = 0;

    private Redis $storage;
    private int $fd;
    public object $attr;

    public function __construct(Redis $storage, int $fd, object $user)
    {
        $this->storage = $storage;
        $this->fd      = $fd;
        $this->attr    = (object)Common::toNums($user);
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
        $this->trans_time = time();
        $this->trans_timeout = $this->trans_time + self::TRANSITION_TIMEOUT;

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
        $app->send($this->fd, ['chloc' => ['trans_time' => $this->trans_time, 'trans_timeout' => $this->trans_timeout]]);
        $app->send($this->fd, ['loc_users' => $app->userRepo->getAllByLoc($to)]);
        $app->locRepo->sendLoc($this);
        $this->save(); // Need to save user ?
    }

    public function getBackPack($app)
    {
        $this->packItems = Common::itemsOnKeys(
            DB::getAll("Select * from items where owner_id = {$this->id}"),
            ['id'],
            function (&$item) use ($app) {
                $item = (object)array_merge((array)$app->itemRepo->getItemById($item->item_id), (array)$item);
            }
        );

        if (!$this->packItems) return;

        $app->send($this->getFd(),
            ['getBackPack' => $this->packItems]
        );
    }

    private function removeItem($itemId)
    {
        // unset($this->packItems[$itemId]);
        // DB::query('DELETE from items where id = ?i', $itemId);
        return true;
    }

    private function wearItem($itemId)
    {
        if (!$this->isFitItem($itemId)) return false;
        if ($this->isUsableItem($itemId)) return false;

        $this->packItems[$itemId]->loc = 'WEARING';
        DB::query("UPDATE items SET loc = 'WEARING' where id = ?i", $itemId);

        return true;
    }

    private function takeoffItem($itemId)
    {
        if (!$this->isFitItem($itemId, false)) return false;

        $this->packItems[$itemId]->loc = 'INVENTORY';
        DB::query("UPDATE items SET loc = 'INVENTORY' where id = ?i", $itemId);

        return true;
    }

    public function itemAction($app, $action, $itemId)
    {
        // dd($this->packItems[$itemId]);
        if (!$this->canAction($action)
            || $this->fight
            || !$this->itemExists($itemId)
        ) return;

        if ($this->{$action}($itemId)) {
            $app->send($this->fd, [$action => 1]);
        }
    }

    private function canAction($action)
    {
        return method_exists($this, $action);
    }

    private function isUsableItem($itemId)
    {
        $item = $this->packItems[$itemId];
        $usableTypes = ['potion', 'scroll'];

        if (!in_array($item->item_type, $usableTypes)) return false;

        $this->use($item);

        return true;
    }

    private function use($item)
    {
        // $result = match ($item->item_type) {
        //     'potion' =>
        // };
    }

    private function isWeared($itemId)
    {
        return $this->packItems[$itemId]->loc == 'WEARING';
    }

    private function isFitItem($itemId, $wear = true)
    {
        if ($wear) {
            if ($this->isWeared($itemId)) return false;
        } else {
            if (!$this->isWeared($itemId)) return false;
        }

        if ($this->packItems[$itemId]->need_level > $this->level) return false;

        return true;
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
            'id'     => $this->getId(),
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
        return $this->attr->{$key} ?? null;
    }

    public function __set($key, $value)
    {
        $this->attr->{$key} = $value;
    }

    public function __unset($key)
    {
        unset($this->attr->{$key});
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
