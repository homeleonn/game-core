<?php

$app = [
    'providers' => [
        Homeleon\Config\ConfigServiceProvider::class,
        Homeleon\Redis\RedisServiceProvider::class,
        Homeleon\DB\DatabaseServiceProvider::class,
        Homeleon\DosProtection\DosProtectionServiceProvider::class,
    ],

    'aliases' => [
        'App' => \Homeleon\Support\Facades\App::class,
        'Router' => \Homeleon\Support\Facades\Router::class,
        'Route' => \Homeleon\Support\Facades\Router::class,
        'Response' => \Homeleon\Support\Facades\Response::class,
        'Request' => \Homeleon\Support\Facades\Request::class,
        'Auth' => \Homeleon\Support\Facades\Auth::class,
        'Config' => \Homeleon\Support\Facades\Config::class,
        'DB' => \Homeleon\Support\Facades\DB::class,
        'Session' => \Homeleon\Support\Facades\Session::class,
        'Validator' => \Homeleon\Support\Facades\Validator::class,
    ]
];

if (!defined('HTTP_SIDE')) {
    return $app;
}

$app['providers'] = array_merge($app['providers'], [
    Homeleon\Session\SessionServiceProvider::class,
    Homeleon\Validation\ValidationServiceProvider::class,
    Homeleon\Http\HttpServiceProvider::class,
    Homeleon\Auth\AuthServiceProvider::class,
    Homeleon\Router\RouterServiceProvider::class,
    Homeleon\Captcha\CaptchaServiceProvider::class,
    App\Http\Providers\RouteServiceProvider::class,
    App\Http\Providers\AppServiceProvider::class,
    App\Services\JWT\JWTServiceProvider::class,
]);

$app['middlewareGroups'] = [
    'web' => [
        \Homeleon\Session\Middleware\StartSession::class,
        \Homeleon\Session\Middleware\ValidateCsrfToken::class
    ],
    'api' => [
        \App\Middleware\ApiMiddleware::class
    ]
];


return $app;
