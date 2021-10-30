<?php

namespace Homeleon\Contracts\Session;

interface Session
{
    public function get(string $key);

    public function set(string $key, $value);

    public function del(string $key);

    public function all();
}
