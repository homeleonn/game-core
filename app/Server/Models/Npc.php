<?php

namespace App\Server\Models;

class Npc extends Unit
{
    protected string $table = 'npc';

    public function __construct(...$args)
    {
        parent::__construct(...$args);

        $this->calculateFullDamage();
        $this->login = $this->name;
    }
}
