<?php

$app = [
    'providers' => [
        Core\Config\ConfigServiceProvider::class,
        Core\Redis\RedisServiceProvider::class,
        Core\DB\DatabaseServiceProvider::class,
        Core\DosProtection\DosProtectionServiceProvider::class,
    ],

    'aliases' => [
        'App' => \Core\Support\Facades\App::class,
        'Router' => \Core\Support\Facades\Router::class,
        'Route' => \Core\Support\Facades\Router::class,
        'Response' => \Core\Support\Facades\Response::class,
        'Request' => \Core\Support\Facades\Request::class,
        'Auth' => \Core\Support\Facades\Auth::class,
        'Config' => \Core\Support\Facades\Config::class,
        'DB' => \Core\Support\Facades\DB::class,
        'Session' => \Core\Support\Facades\Session::class,
    ]
];

if (!defined('HTTP_SIDE')) {
    return $app;
}

$app['providers'] = array_merge($app['providers'], [
    Core\Session\SessionServiceProvider::class,
    Core\Http\HttpServiceProvider::class,
    Core\Auth\AuthServiceProvider::class,
    Core\Router\RouterServiceProvider::class,
    App\Http\Providers\RouteServiceProvider::class,
]);

return $app;
