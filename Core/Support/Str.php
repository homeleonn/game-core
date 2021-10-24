<?php

namespace Core\Support;

class Str
{
    public static function addStartSlash($str)
    {
        if (!str_starts_with($str, '/')) {
            return '/' . $str;
        }

        return $str;
    }

    public static function random($length = 20): string
    {
        return substr(str_shuffle(str_repeat('0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ', 3)), 0, $length);
    }
}
