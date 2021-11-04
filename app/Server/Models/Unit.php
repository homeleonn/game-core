<?php

namespace App\Server\Models;

class Unit extends AppModel
{
    public int $min_damage = 0;
    public int $max_damage = 0;

    protected function calculateFullDamage()
    {
        $this->calculateDamageByPower();

        if (!$this->isBot()) {
            [$min, $max] = $this->calculateDamageByItems();
            $this->min_damage += $min ?? 0;
            $this->max_damage += $max ?? 0;
        }
    }

    private function calculateDamageByPower()
    {
        $this->min_damage = floor($this->power / 2);
        $this->max_damage = $this->power + $this->min_damage;
    }

    private function calculateDamageByItems()
    {
        return array_reduce($this->items, function ($carry, $item) {
            if ($item->loc == 'INVENTORY') return $carry;

            $carry[0] += $item->min_damage;
            $carry[1] += $item->max_damage;

            return $carry;
        }, [0, 0]);
    }

    public function isBot()
    {
        return isset($this->aggr);
    }

    public function percentOfHp()
    {
        return $this->curhp / $this->maxhp * 100;
    }

    public function send($message)
    {
        \App::make('game')->send($this->getFd(), $message);
    }

    public function restore()
    {
        if ($this->isBot() || $this->curhp >= $this->maxhp) return;

        $minutesToMaxHp = 1;
        $restoreSpeed = 1;
        $restoreOneSecond = $this->maxhp / ($minutesToMaxHp / $restoreSpeed) / 60;

        $this->curhp = $this->curhp + (time() - $this->last_restore) * $restoreOneSecond;
        $this->last_restore = time();

        if ($this->curhp >= $this->maxhp) $this->curhp = $this->maxhp;
    }
}
