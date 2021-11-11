<?php

namespace Homeleon\Captcha;

use Homeleon\Support\ServiceProvider;
use Homeleon\Session\Session;

class CaptchaServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->set(Captcha::class, function ($app) {
            return new Captcha($app->make(Session::class));
        });
    }
}
