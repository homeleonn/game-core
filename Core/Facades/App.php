<?php

namespace Core\Facades;

class App extends Facade
{
	public static function getFacadeAccessor()
	{
		return 'app';
	}
}