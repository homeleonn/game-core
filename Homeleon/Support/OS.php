<?php

namespace Homeleon\Support;

class OS
{
    public static function isWin()
    {
        return strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
    }
}
