<?php

namespace Homeleon\Auth;

use Homeleon\Support\ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->set(Auth::class, function ($app) {
            return new Auth($app->db, $app->session);
        });
    }
}
