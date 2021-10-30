<?php

namespace Homeleon\DosProtection;

use Homeleon\Support\Facades\Config;
use Homeleon\Support\ServiceProvider;

class DosProtectionServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->set('dosprotection', function($app) {
            return new DosProtection(Config::get('throttle'));
        });
    }
}
