<?php

namespace App\Http\Providers;

use Homeleon\Support\ServiceProvider;
use Homeleon\Router\Route;

class RouteServiceProvider extends ServiceProvider
{
    public function boot()
    {
        Route::pattern('id', '\d+');
    }

    public function register() {}
}
