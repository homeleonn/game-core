<?php

namespace App\Http\Providers;

use Core\Support\ServiceProvider;
use Core\Router\Route;

class RouteServiceProvider extends ServiceProvider
{
    public function boot()
    {
        Route::pattern('id', '\d+');
    }

    public function register() {}
}
