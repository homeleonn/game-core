<?php

namespace App\Server\Models;

class Loc
{
    public function __construct($loc)
    {
        $this->attr = $loc;
    }

    public function __call($method, $args)
    {
        if (preg_match('/^get(.+)/', $method, $matches)) {
            return $this->attr->{lcfirst($matches[1])} ?? null;
        }
    }

    private function __get($key)
    {
        return $this->attr->$key ?? null;
    }
}
