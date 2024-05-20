<?php

namespace Homeleon\Config;


class Config
{
    private array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function get(string $key): mixed
    {
        return $this->config[$key] ?? null;
    }

    public function set($key, $value): void
    {
        $this->config[$key] = $value;
    }
}
