<?php

namespace Core\Router;

use Core\Support\ServiceProvider;
use Core\Http\{Request, Response};

class RouterServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->set(Router::class, function ($app) {
            return new Router(
                $app->make(Request::class),
                $app->make(Response::class)
            );
        });
    }
}
