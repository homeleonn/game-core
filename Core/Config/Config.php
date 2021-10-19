<?php

namespace Core\Config;

use Exception;

class Config
{

    private $config;

    public function __construct($config)
    {
        $this->config = $config;
    }

    public function get($key)
    {
        if (!isset($this->config[$key])) {
            new Exception("Config '{$key}' doesn't exists!");
        }

        return $this->config[$key];
    }
}
