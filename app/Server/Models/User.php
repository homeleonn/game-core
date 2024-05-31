<?php

namespace App\Server\Models;

use App\Server\Application;
use Homeleon\Support\{Common, Obj};
use Homeleon\Support\Facades\DB;

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
    private array $needUpdateChars = ['power', 'critical', 'evasion', 'stamina', 'curhp', 'maxhp', 'min_damage', 'max_damage'];


    public function getExtra(): array
    {
        $fields = ['min_damage', 'max_damage', 'extra_min_damage', 'extra_max_damage'];
        $extra = [];

        foreach ($fields as $field) {
            $extra[$field] = $this->{$field};
        }

        return $extra;
    }

    public function isAdmin(): bool
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

    public function chloc(int $to, Application $app)
    {
        if (!$this->canTransition()) {
            $app->send($this->fd, ['transition_timeout' => null]);
            return;
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

    public function getBackPack(Application $app): void
    {
        if (!$this->items) return;

        $app->send($this->getFd(),
            ['getBackPack' => array_values($this->items)]
            // ['getBackPack' => $this->items]
        );
    }

    public function loadItems(): void
    {
        if (!$items = Item::where('owner_id', $this->id)->all() ?? []) return;
        $items = Common::itemsOnKeys2($items, ['id', 'item_id' => null]);
        // dd($items);
        $this->items = $items['id'];
        $this->itemsByItemId = $items['item_id'];

        foreach ($this->items as $key => $item) {
            if ($item->count <= 0) {
                unset($this->items[$key]);
                $item->delete();
                continue;
            }
            $item->setAttrs((array)repo('item')->getItemById($item->item_id));
        }
    }

    /**
     * @return Item[]
     */
    public function getItems(): array
    {
        return $this->items;
    }

    public function getItemsByItemId(int $itemId): ?array
    {
        return $this->itemsByItemId[$itemId] ?? null;
    }

    public function getItemByItemId(int $itemId): ?Item
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

    public function getQuests(): void
    {
        $userQuests = DB::getAll("SELECT quest_id, completed FROM user_quests WHERE user_id = $this->id AND step = 0");
        array_walk(
            $userQuests,
            function ($q) {
                Obj::merge($q, Common::propsOnly((object)repo('quest')->getById($q->quest_id), ['name', 'data'], true));
                $q->data = $q->data['description'];
            }
        );
        $this->send([
            'getQuests' => $userQuests
        ]);
    }

    private function processingChars(Item $item, string $action)
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

    private function deleteItem(int $itemId): ?bool
    {
        if (!isset($this->items[$itemId])) return null;
        $this->items[$itemId]->delete();
        unset($this->items[$itemId]);

        return true;
    }

    private function removeItem(int $itemId): ?bool
    {
        if ($this->isWeared($itemId)) return null;

        return $this->deleteItem($itemId);
    }

    private function wearItem(int $itemId): bool
    {
        if (!$this->isFitItem($itemId)) return false;
        if ($this->isUsableItem($itemId)) return false;

        $this->items[$itemId]->loc = 'WEARING';
        DB::query("UPDATE items SET loc = 'WEARING' where id = ?i", $itemId);

        return true;
    }

    private function takeoffItem(int $itemId): bool
    {
        if (!$this->isFitItem($itemId, false)) return false;

        $this->items[$itemId]->loc = 'INVENTORY';
        DB::query("UPDATE items SET loc = 'INVENTORY' where id = ?i", $itemId);

        return true;
    }

    public function itemAction(Application $app, string $action,int $itemId): void
    {
        // dd($this->items[$itemId]);
        if (!$this->canAction($action)
            || $this->fight
            || !$this->itemExists($itemId)
        ) return;

        DB::beginTransaction();
        if ($this->{$action}($itemId)) {
            if (in_array($action, [self::ITEM_WEARING, self::ITEM_TAKEOFF])) {
                $this->restore();
                $this->processingChars($this->items[$itemId], $action);
                $this->sendChars($app, $this->needUpdateChars);
            }
            $app->send($this->fd, [$action => $itemId]);
        }
        DB::commit();
    }

    public function sendChars(Application $app, array $chars): void
    {
        $this->restore();

        $app->send($this->fd, ['me' => Common::propsOnly($this, $chars)]);
    }

    private function canAction(string $action): bool
    {
        return method_exists($this, $action);
    }

    private function isUsableItem(int $itemId): bool
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

    private function isWeared(int $itemId): bool
    {
        return $this->items[$itemId]->loc == 'WEARING';
    }

    private function isFitItem(int $itemId, bool $wear = true): bool
    {
        if ($wear) {
            if ($this->isWeared($itemId)) return false;
        } else {
            if (!$this->isWeared($itemId)) return false;
        }

        if ($this->items[$itemId]->need_level > $this->level) return false;

        return true;
    }

    private function itemExists($itemId): bool
    {
        return isset($this->items[$itemId]);
    }

    public function canTransition(): bool
    {
        return $this->trans_timeout <= time();
    }

    public function prepareForFight(): bool
    {
        $this->restore();
        if ($this->percentOfHp() < 33) {
            $this->send(['error' => 'Hit points are too few']);
            return false;
        }

        return true;
    }

    public function locProps(): array|object
    {
        $props = (int)$this->clan
               ? ['id', 'login', 'level', 'clan_name', 'clan_img', 'tendency_name', 'tendency_img']
               : ['id', 'login', 'level'];

        return Common::propsOnly($this, $props);
    }

    public function getAll(): array
    {
        $this->restore();
        return array_merge($this->attr, $this->getExtra());
    }

    public function getFd(): int
    {
        return $this->fd;
    }

    public function setFd($fd): void
    {
        $this->fd = $fd;
    }

    public function send(mixed $message = null): void
    {
        send($this->fd, $message);
    }

    public function getDataForLocation(): array
    {
        return [$this->loc, $this->fd, $this->id];
    }

    public function clearMarkExit(): void
    {
        $this->exit = null;
    }

    public function markExit(): void
    {
        $this->exit = time() + self::CLEAR_EXITERS_TIMEOUT;
    }

    public function isExit(): ?int
    {
        return $this->exit;
    }

    public function addExp(int $value): void
    {
        $levelExp = [
            1 => 35,
            2 => 70,
            3 => 105,
            4 => 140,
            5 => 175,
            6 => 210,
            7 => 245,
            8 => 280,
        ];
        $this->exp = $this->exp + $value;
        foreach ($levelExp as $level => $exp) {
            if ($this->exp < $exp) {
                break;
            }
        }

        if ($this->level < $level) {
            $this->level = $level;
            $this->send(['message' => "<b>Поздравляем! Ваш уровень повысился. Теперь {$level}й.</b>"]);
        }
    }

    public function __toString(): string
    {
        return $this->asString();
    }

    public function asString(): string
    {
        return "fd:{$this->fd} id:{$this->id} name:{$this->login} loc:{$this->loc}";
    }

    public function __debugInfo()
    {
        return $this->attr;
    }
}
