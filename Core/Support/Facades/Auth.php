<?php

namespace Core\Support\Facades;

class Auth extends Facade
{
	public static function getFacadeAccessor()
	{
		return 'auth';
	}
}