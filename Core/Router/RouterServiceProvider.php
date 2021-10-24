<?php

namespace Core\Router;

use Core\Support\ServiceProvider;

class RouterServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->set('router', function ($app) {
            return new Router(
                $app->make('request'),
                $app->make('response')
            );
        });
    }
}
