<?php

namespace Homeleon\Support\Facades;

class Session extends Facade
{
    public static function getFacadeAccessor()
    {
        return 'session';
    }
}
