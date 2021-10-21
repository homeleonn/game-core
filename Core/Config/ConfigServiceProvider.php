<?php

namespace Core\Config;

use Core\Support\ServiceProvider;

class ConfigServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->set('config', function ($app) {
            return new Config($this->loadConfig());
        });
    }

    public function loadConfig()
    {
        return require ROOT . '/.env.php';
    }
}
