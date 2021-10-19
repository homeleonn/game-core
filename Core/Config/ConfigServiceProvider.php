<?php

namespace Core\Config;

use Core\Support\ServiceProvider;

class ConfigServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->set('config', function($app) {
            return new Config($app->config);
        });
    }
}
