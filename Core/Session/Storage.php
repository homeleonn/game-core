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

    public function set(string $key, $value): void
    {
        $_SESSION[$key] = (string)$value;
    }

    public function del(string $key): void
    {
        unset($_SESSION[$key]);
    }

    public function all(): mixed
    {
         return $_SESSION;
    }
}
