<?php

namespace Core\DB;

use Core\Support\Facades\Config;
use Core\Support\ServiceProvider;

class DatabaseServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->set(DB::class, function ($app) {
            return new DB($app->config->get('db'));
        });
    }
}
