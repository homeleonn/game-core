<?php

namespace Core\Facades;

class DB extends Facade
{
	public static function getFacadeAccessor()
	{
		return 'db';
	}
}