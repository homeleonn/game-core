<?php

namespace App\Core;


class Request
{
    private array $server;
    private string $path;
    private string $method;

    public function __construct()
    {
        $this->server = $_SERVER;
        $this->method = $_SERVER['REQUEST_METHOD'] ?? '/';
        $this->parseUrl();
    }

    private function parseUrl(): void
    {
        $urlParts = parse_url($_SERVER['REQUEST_URI']);
        $this->path = trim($urlParts['path'], '/');
    }

    public function getPath()
    {
        return $this->path;
    }

    public function getMethod()
    {
        return $this->method;
    }
}