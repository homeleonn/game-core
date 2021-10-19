<?php

$app = [
    'providers' => [
        Core\Config\ConfigServiceProvider::class,
        Core\Session\SessionServiceProvider::class,
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
    ]
];

if (!defined('HTTP_SIDE')) {
    return $app;
}

$app['providers'] = array_merge($app['providers'], [
    Core\Http\HttpServiceProvider::class,
    Core\Auth\AuthServiceProvider::class,
    Core\Router\RouterServiceProvider::class,
    App\Http\Providers\RouteServiceProvider::class,
]);

return $app;
