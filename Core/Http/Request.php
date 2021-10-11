<?php

namespace Core\Http;

use Exception;

class Request
{
    private array $server;
    private string $uri;
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
        $this->uri = \prepareUri($urlParts['path']);
    }

    public function get($param)
    {
        return $_REQUEST[$param] ?? null;
    }

    public function getUri()
    {
        return $this->uri;
    }

    public function getMethod()
    {
        return $this->method;
    }

    public function all()
    {
        return $_REQUEST;
    }

    public function only(array $only)
    {
        $res = [];

        foreach ($only as $param) {
            // if (!isset($_REQUEST[$item])) {
            //     // throw new Exception("Request item {$item} not found");
            //     // return null;
            // }

            $res[$param] = $this->get($param);
        }

        return $res;
    }

    public function except(array $except)
    {
        $req = $_REQUEST;

        foreach ($except as $item) {
            if (isset($req[$item])) {
                unset($req[$item]);
            }
        }

        return $req;
    }
}