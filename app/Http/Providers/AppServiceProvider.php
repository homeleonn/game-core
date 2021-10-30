<?php

namespace App\Http\Providers;

use Homeleon\Support\ServiceProvider;
use Homeleon\Support\Facades\DB;
use stdClass;

class AppServiceProvider extends ServiceProvider
{
    public function boot()
    {
        DB::setFetchMode(stdClass::class);
    }

    public function register() {}
}
