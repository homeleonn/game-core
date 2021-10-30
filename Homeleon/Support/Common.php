<?php

namespace Homeleon\Support;

use Closure;

class Common
{
    public static function itemsOnKeys(array|object $items, array $keys, Closure $cb = null)
    {
        $itemsOnKey = [];

        foreach ($items as $item) {
            foreach ($keys as $k => $key) {
                if (is_array($item)) {
                    $isArray = true;
                    $item = (object)$item;
                }

                if (!isset($item->{$key})) {
                    throw new \Exception('Key \'' . $key . '\' is not exists');
                }

                if ($cb) {
                    $cb($item);
                }

                $itemsOnKey[$item->{$key}] = isset($isArray) ? (array)$item : $item;
            }
        }
        if (empty($itemsOnKey)) return false;
        return $itemsOnKey;
    }

    public static function propsOnly(object $obj, array $keys, bool $likeObject = false): array|object
    {
        $resultArray = [];

        foreach ($keys as $key) {
            $resultArray[$key] = $obj->{$key} ?? null;
        }

        return $likeObject ? (object)$resultArray : $resultArray;
    }

    public static function toNums($arr)
    {
        return array_map(function ($item) {
            if (is_numeric($item)) {
                return ctype_digit($item) ? (int)$item : (float)$item;
            } else {
                return $item;
            }
        }, (array)$arr);
    }

    public static function joinBufferLines($cb)
    {
        ob_start();
        $cb();
        $content = ob_get_contents();
        ob_end_clean();

        return preg_replace(['/[\n]/m', '/(\t|\s)+/'], ' ', $content);
    }
}
