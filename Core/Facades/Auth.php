<?php

namespace Core\Facades;

class Auth extends Facade
{
	public static function getFacadeAccessor()
	{
		return 'auth';
	}
}