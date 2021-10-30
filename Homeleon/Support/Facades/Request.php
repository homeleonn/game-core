<?php

namespace Homeleon\Support\Facades;

class Request extends Facade
{
    public static function getFacadeAccessor()
    {
        return 'request';
    }
}
