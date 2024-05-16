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
                array_merge($_REQUEST, json_decode(file_get_contents('php://input'), true) ?? []),
                $this->app->make(Session::class),
                $this->app->make(Validator::class),
            );
        });

        $this->app->set(Response::class, function () {
            return new Response();
        });
    }
}
