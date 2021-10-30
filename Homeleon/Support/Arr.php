<?php

namespace Homeleon\Support;

class Arr
{
    public static function find($haystack, $needle): mixed
    {
        if (array_key_exists($needle, $haystack)) return $haystack[$needle];
        if (strpos($needle, '.') === false) return null;

        $keys       = explode('.', $needle);
        $keyCount   = count($keys);
        $found      = &$haystack;

        for ($i = 0; $i < $keyCount; $i++) {
            if (!array_key_exists($keys[$i], $found)) return null;

            $found = &$found[$keys[$i]];
        }

        return $found;
    }

    public static function &last(array &$arr): mixed
    {
        return $arr ? $arr[array_key_last($arr)] : null;
    }
}
