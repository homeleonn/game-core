<?php

namespace Core\Auth;

use Core\Support\ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->set('auth', function ($app) {
            return new Auth($app->db, $app->storage);
        });
    }
}
