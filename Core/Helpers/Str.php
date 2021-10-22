<?php

namespace Core\Helpers;

class Str
{
    public static function addstartSlash($str)
    {
        if (!str_starts_with($str, '/')) {
            return '/' . $str;
        }

        return $str;
    }
}
