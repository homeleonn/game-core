<?php

namespace Homeleon\Router;

use Homeleon\Support\ServiceProvider;
use Homeleon\Http\{Request, Response};

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
