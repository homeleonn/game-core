<?php


namespace Core\Facades;

use Core\App;
use Exception;


abstract class Facade
{
	protected static App $app;
	protected static array $resolvedInstances = [];

	public static function setFacadeApplication(App $app)
	{
		static::$app = $app;
	}

	protected static function getFacadeAccessor()
	{
		throw new Exception('getFacadeAccessor was not defined');
	}

	public static function __callStatic($method, $args)
	{
		$name = static::getFacadeAccessor();

		if (!isset(static::$resolvedInstances[$name])) {
			static::$resolvedInstances[$name] = self::$app->make($name);
		}

		$instance = static::$resolvedInstances[$name];

		return $instance->$method(...$args);
	}

}