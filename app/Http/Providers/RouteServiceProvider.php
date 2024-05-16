<?php

namespace App\Http\Providers;

use Homeleon\Support\ServiceProvider;
use Homeleon\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    public function boot()
    {
        Route::pattern('id', '\d+');

        $this->routes(function () {
            Route::middleware('api')
                ->prefix('api/v1')
                ->group(root() . '/routes/api.php');

            Route::middleware('web')
                ->group(root() . '/routes/web.php');
        });
    }

    public function register() {}
}
