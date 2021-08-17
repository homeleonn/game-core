<?php

namespace Core\Facades;

class Route extends Facade
{
	public static function getFacadeAccessor()
	{
		return 'router';
	}
}