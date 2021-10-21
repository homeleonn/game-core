<?php

namespace Core\Session;

use Core\Contracts\Session\Session;
use SessionHandlerInterface;

class Storage implements Session
{
    // public function __construct()
    // {
    // }

    public function get(?string $key = null)
    {
        return is_null($key) ? $_SESSION : ($_SESSION[$key] ?? null);
    }

    public function set(string $key, $value)
    {
        $_SESSION[$key] = $value;
    }

    public function del(string $key)
    {
        unset($_SESSION[$key]);
    }

    public function all()
    {
        // return
    }
}
