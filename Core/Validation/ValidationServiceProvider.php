<?php

namespace Core\Validation;

use Core\Support\ServiceProvider;

class ValidationServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->set(Validator::class, function () {
            return new Validator();
        });
    }
}
