<?php

namespace App\Server\Models;

class Npc extends AppModel
{
    protected string $table = 'npc';

    public function __construct(...$args)
    {
        parent::__construct(...$args);

        [$this->min_damage, $this->max_damage] = User::calculateDamage($this->power);
        $this->login = $this->name;
    }
}
