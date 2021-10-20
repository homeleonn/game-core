<?php

namespace Core\Config;

use Exception;

class Config
{
    private array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function get(string $key): mixed
    {
        if (!isset($this->config[$key])) {
            throw new Exception("Config '{$key}' doesn't exists!");
        }

        return $this->config[$key];
    }
}
