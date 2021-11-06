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

    public static function only(object &$obj, array $only)
    {
        foreach ($obj as $key => $value) {
            if (!in_array($key, $only)) {
                unset($obj->{$key});
            }
        }
    }
}
