<?php

namespace Core\Http;

use Core\Support\ServiceProvider;

class HttpServiceProvider extends ServiceProvider
{
    public function register()
    {
        // $this->app->bind(Request::class, function() {
        //     return new Request();
        // });
        // $this->app->alias('request', Request::class)

        $this->app->set(Request::class, 'request');
        $this->app->set('request', function() {
            return new Request();
        });

        $this->app->set('response', function() {
            return new Response();
        });
    }
}
