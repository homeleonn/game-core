<?php

namespace Core\Helpers;

use Closure;

class Common
{
	public static function itemsOnKeys($items, $keys, Closure $cb = null){
		if(!is_array($items)){
			throw new \Exception('Argument $items not array');
		}
		if(!is_array($keys)){
			throw new \Exception('Argument $keys not array');
		}
		$itemsOnKey = [];
		foreach($items as $item){
			foreach($keys as $k => $key){
				if(!isset($item->{$key})){
					throw new \Exception('Key \'' . $key . '\' is not exists');
				}
				if ($cb) $cb($item);
				$itemsOnKey[$item->{$key}] = $item;
			}
		}
		if(empty($itemsOnKey)) return false;
		return $itemsOnKey;
	}

	public static function propsOnly(object $obj, array $keys, bool $likeObject = false): array
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
}