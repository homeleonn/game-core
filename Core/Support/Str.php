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

    public static function lastPart(string $string, string $separator): string
    {
        $parts = explode($separator, $string);

        return array_pop($parts);
    }

    public static function plural($word)
    {
        $lastLetter = substr($word, -1);

        if ($lastLetter == 'y') {
            return substr_replace($word, 'ies', -1);
        }

        return $word . 's';
    }
}
