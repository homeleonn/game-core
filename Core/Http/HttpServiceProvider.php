<?php

namespace Core\Http;

use Core\Support\ServiceProvider;

class HttpServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->set(Request::class, function () {
            return new Request($_SERVER, $_REQUEST);
        });

        $this->app->set(Response::class, function () {
            return new Response();
        });
    }
}
