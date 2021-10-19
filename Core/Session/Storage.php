<?php

namespace Core\Session;

use Core\Contracts\Session\Session;
use SessionHandlerInterface;

class Storage implements Session
{
    private $handler;

    public function __construct(SessionHandlerInterface $handler)
    {
        $this->handler = $handler;
    }

    public function get(string $key)
    {
        return $this->handler->read($key);
    }

    public function set(string $key, $value)
    {
        return $this->handler->write($key, $value);
    }

    public function del(string $key)
    {
        return $this->handler->destroy($key);
    }
}
