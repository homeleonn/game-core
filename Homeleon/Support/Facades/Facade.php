<?php

namespace Homeleon\Support\Facades;

use Homeleon\App;
use Exception;

abstract class Facade
{
    protected static App $app;
    protected static array $resolvedInstances = [];

    public static function setFacadeApplication(App $app, $aliases)
    {
        static::$app = $app;
        static::setFacadesAutoload($aliases);
    }

    protected static function setFacadesAutoload(array $aliases): void {
        spl_autoload_register(function($className) use ($aliases) {
            if (isset($aliases[$className]) && class_exists($aliases[$className])) {
                require_once ROOT . '/' . str_replace('\\', '/', $aliases[$className]) . '.php';
                class_alias($aliases[$className], $className);
            }
        });
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
