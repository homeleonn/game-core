<?php

namespace App\Server\Models;

use Redis;
use Homeleon\Support\Common;
use Homeleon\Support\Facades\DB;
use Exception;

class User extends Unit
{
    const CLEAR_EXITERS_TIMEOUT = 10;
    const CAN_TRANSITION_YES = 1;
    const CAN_TRANSITION_NO = 0;
    const TRANSITION_TIMEOUT = 5;

    const ITEM_REMOVE_YES = 1;
    const ITEM_REMOVE_NO = 0;

    const ITEM_WEARING = 'wearItem';
    const ITEM_TAKEOFF = 'takeoffItem';

    protected int $fd;
    protected ?int $exit = null;
    protected array $items = [];
    public array $itemsByItemId = [];
    public int $extra_min_damage = 0;
    public int $extra_max_damage = 0;
    private $needUpdateChars = ['power', 'critical', 'evasion', 'stamina', 'curhp', 'maxhp', 'min_damage', 'max_damage'];


    public function getExtra()
    {
        $fields = ['min_damage', 'max_damage', 'extra_min_damage', 'extra_max_damage'];
        $extra = [];

        foreach ($fields as $field) {
            $extra[$field] = $this->{$field};
        }

        return $extra;
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
        $this->save();
    }

    public function getBackPack($app)
    {
        if (!$this->items) return;

        $app->send($this->getFd(),
            ['getBackPack' => array_values($this->items)]
        );
    }

    public function loadItems()
    {
        if (!$items = Item::where('owner_id', $this->id)->all() ?? []) return;
        $items = Common::itemsOnKeys2($items, ['id', 'item_id' => null]);
        // dd($items);
        $this->items = $items['id'];
        $this->itemsByItemId = $items['item_id'];

        array_walk(
            $this->items,
            fn ($item) => $item->setAttrs((array)repo('item')->getItemById($item->item_id))
        );
    }

    public function getItems()
    {
        return $this->items;
    }

    public function getItemsByItemId($itemId)
    {
        return $this->itemsByItemId[$itemId] ?? null;
    }

    public function getItemByItemId($itemId)
    {
        return $this->itemsByItemId[$itemId][0] ?? null;
    }

    public function countOfItems(int $itemId): int
    {
        if (!isset($this->itemsByItemId[$itemId])) return 0;

        return $this->itemsByItemId[$itemId][0]->stackable
                    ? $this->itemsByItemId[$itemId][0]->count
                    : count($this->itemsByItemId[$itemId]);
    }

    public function addItem($itemId, $count = 1)
    {
        repo('item')->addToUser($this->id, $itemId, $count, $userItems);
    }

    private function processingChars($item, $action)
    {
        $isOn = $action == self::ITEM_WEARING;

        foreach ($this->needUpdateChars as $userChar) {
            $itemChar = in_array($userChar, ['curhp', 'maxhp'])
                      ? 'hp'
                      : $userChar;

            $this->{$userChar} += ($isOn ? $item->{$itemChar} : -$item->{$itemChar});
        }

        $this->calculateFullDamage();
        $this->restore();

        $this->save();
    }

    private function deleteItem($itemId)
    {
        if (!isset($this->items[$itemId])) return;
        $this->items[$itemId]->delete();
        unset($this->items[$itemId]);

        return true;
    }

    private function removeItem($itemId)
    {
        if ($this->isWeared($itemId)) return;

        return $this->deleteItem($itemId);
    }

    private function wearItem($itemId)
    {
        if (!$this->isFitItem($itemId)) return false;
        if ($this->isUsableItem($itemId)) return false;

        $this->items[$itemId]->loc = 'WEARING';
        DB::query("UPDATE items SET loc = 'WEARING' where id = ?i", $itemId);

        return true;
    }

    private function takeoffItem($itemId)
    {
        if (!$this->isFitItem($itemId, false)) return false;

        $this->items[$itemId]->loc = 'INVENTORY';
        DB::query("UPDATE items SET loc = 'INVENTORY' where id = ?i", $itemId);

        return true;
    }

    public function itemAction($app, $action, $itemId)
    {
        // dd($this->items[$itemId]);
        if (!$this->canAction($action)
            || $this->fight
            || !$this->itemExists($itemId)
        ) return;

        DB::beginTransaction();
        if ($this->{$action}($itemId)) {
            if (in_array($action, [self::ITEM_WEARING, self::ITEM_TAKEOFF])) {
                $this->processingChars($this->items[$itemId], $action);
                $this->sendChars($app, $this->needUpdateChars);
            }
            $app->send($this->fd, [$action => $itemId]);
        }
        DB::commit();
    }

    public function sendChars($app, $chars)
    {
        $this->restore();

        $app->send($this->fd, ['me' => Common::propsOnly($this, $chars)]);
    }

    private function canAction($action)
    {
        return method_exists($this, $action);
    }

    private function isUsableItem($itemId)
    {
        $item = $this->items[$itemId];
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
        return $this->items[$itemId]->loc == 'WEARING';
    }

    private function isFitItem($itemId, $wear = true)
    {
        if ($wear) {
            if ($this->isWeared($itemId)) return false;
        } else {
            if (!$this->isWeared($itemId)) return false;
        }

        if ($this->items[$itemId]->need_level > $this->level) return false;

        return true;
    }

    private function itemExists($itemId)
    {
        return isset($this->items[$itemId]);
    }

    public function canTransition()
    {
        return $this->trans_timeout <= time();
    }

    public function locProps()
    {
        $props = (int)$this->clan
               ? ['id', 'login', 'level', 'clan_name', 'clan_img', 'tendency_name', 'tendency_img']
               : ['id', 'login', 'level'];

        return Common::propsOnly($this, $props);
    }

    public function getAll()
    {
        $this->restore();
        return array_merge($this->attr, $this->getExtra());
    }

    public function getFd()
    {
        return $this->fd;
    }

    public function setFd($fd)
    {
        $this->fd = $fd;
    }

    public function send($message)
    {
        send($this->fd, $message);
    }

    public function getDataForLocation()
    {
        return [$this->loc, $this->fd, $this->id];
    }

    public function clearMarkExit()
    {
        $this->exit = null;
    }

    public function markExit()
    {
        $this->exit = time() + self::CLEAR_EXITERS_TIMEOUT;
    }

    public function isExit()
    {
        return $this->exit;
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
        return $this->attr;
    }
}
