<?php

namespace Homeleon\Support;

use Homeleon\App;

abstract class ServiceProvider
{
    protected App $app;

    public function __construct(App $app)
    {
        $this->app = $app;
    }


    public function boot(){}
    abstract public function register();
}
