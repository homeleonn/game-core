<?php

namespace Core\Support\Facades;

class DB extends Facade
{
    public static function getFacadeAccessor()
    {
        return 'db';
    }
}
