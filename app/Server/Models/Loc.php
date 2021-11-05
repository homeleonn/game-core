<?php

namespace App\Server\Models;

class Loc extends AppModel
{
    protected string $table = 'locations';

    public function __toString()
    {
        return $this->attr;
    }
}
