<?php

namespace Homeleon\DB;

use Homeleon\Support\Facades\Config;
use Homeleon\Support\ServiceProvider;

class DatabaseServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->set(DB::class, function ($app) {
            return new DB($app->config->get('db'));
        });
    }
}
