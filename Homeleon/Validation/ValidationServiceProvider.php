<?php

namespace Homeleon\Validation;

use Homeleon\Support\ServiceProvider;

class ValidationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->set(Validator::class, function () {
            return new Validator();
        });
    }
}
