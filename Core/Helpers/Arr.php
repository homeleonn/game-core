<?php

namespace Core\Helpers;

class Arr
{
    public static function find($haystack, $needle): ?mixed
    {
        if (array_key_exists($needle, $haystack)) return $haystack[$needle];
        if (strpos($needle, '.') === false) return;

        $keys       = explode('.', $needle);
        $keyCount   = count($keys);
        $finded     = &$haystack;

        for ($i = 0; $i < $keyCount; $i++) {
            if (!array_key_exists($keys[$i], $finded)) return;

            $finded = &$finded[$keys[$i]];
        }

        return $finded;
    }

    public static function &last(array &$arr)
    {
        return $arr[array_key_last($arr)];
    }
}
