<?php

class Common
{
	public function itemsOnKeys($items, $keys){
		if(!is_array($items)){
			throw new \Exception('Argument $items not array');
		}
		if(!is_array($keys)){
			throw new \Exception('Argument $keys not array');
		}
		$itemsOnKey = [];
		foreach($items as $item){
			foreach($keys as $k => $key){
				// dd($item, $key);
				if(!isset($item->{$key})){
					throw new \Exception('Key \'' . $key . '\' is not exists');
				}
				$itemsOnKey[$item->{$key}][] = $item;
			}
		}
		if(empty($itemsOnKey)) return false;
		return $itemsOnKey;
	}
}