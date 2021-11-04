<?php

namespace Homeleon\Support;

class Obj
{
    public static function merge($obj1, $obj2)
    {
        foreach ($obj2 as $key => $value) {
            $obj1->{$key} = $value;
        }
    }
}
