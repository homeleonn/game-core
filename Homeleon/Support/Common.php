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

    public static function itemsOnKeys1(array $items, array $keys, Closure $cb = null)
    {
        $itemsOnKey = [];

        foreach ($items as $item) {
            if ($cb) $cb($item);
            foreach ($keys as $k => $key) {
                $itemsOnKey[$key][$item->{$key}][] = $item;
            }
        }

        return $itemsOnKey;
    }

    public static function itemsOnKeys2(array $items, array $keys, Closure $cb = null)
    {
        $itemsOnKey = [];

        foreach ($items as $item) {
            if ($cb) $cb($item);
            foreach ($keys as $k => $key) {
                if (is_int($k)) {
                    $itemsOnKey[$key][$item->{$key}] = $item;
                } else {
                    $itemsOnKey[$k][$item->{$k}][] = $item;
                }
            }
        }

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

    public static function arrayPropsOnly(array $objects, array $keys): array|object
    {
        $resultArray = [];

        foreach ($objects as $obj) {
            $onlyProps = [];
            foreach ($keys as $key) {
                $onlyProps[$key] = $obj->{$key} ?? null;
            }

            $resultArray[] = $onlyProps;
        }

        return $resultArray;
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

    public static function exportJsonFields(object|array $ctx, array $fields)
    {
        if (!is_array($ctx)) $ctx = [$ctx];

        foreach ($ctx as $c) {
            foreach ($fields as $field) {
                $c->{$field} = json_decode($c->{$field});
            }
        }
    }
}
