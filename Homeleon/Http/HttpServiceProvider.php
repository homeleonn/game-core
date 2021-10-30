<?php

namespace Homeleon\Http;

use Homeleon\Support\ServiceProvider;
use Homeleon\Validation\Validator;
use Homeleon\Session\Session;

class HttpServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->set(Request::class, function () {
            return new Request(
                $_SERVER,
                $_REQUEST,
                $this->app->make(Session::class),
                $this->app->make(Validator::class),
            );
        });

        $this->app->set(Response::class, function () {
            return new Response();
        });
    }
}
