<?php

namespace Core\Http;

use Exception;
use Core\Support\Str;

class Request
{
    private array $server;
    private array $request;
    private string $uri;
    private string $method;

    public function __construct(array $server, array $request)
    {
        $this->server   = $server;
        $this->method   = $this->server['REQUEST_METHOD'] ?? 'GET';
        $this->uri      = $this->parseUrl();
        $this->request  = $this->sanitizeRequest($request);
    }

    private function parseUrl(): string
    {
        $urlParts = parse_url($this->server['REQUEST_URI']);

        return Str::addStartSlash($urlParts['path']);
    }

    private function sanitizeRequest(array $request): array
    {
        foreach ($request as $key => $value) {
            unset($request[$key]);
            [$key, $value] = str_replace(['"', '\'', '<'], '',  [$key, $value]);
            $request[$key] = $value;
        }

        return $request;
    }

    public function get($param)
    {
        return $this->request[$param] ?? null;
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
        return $this->request;
    }

    public function only(array $only)
    {
        $res = [];

        foreach ($only as $param) {
            $res[$param] = $this->get($param);
        }

        return $res;
    }

    public function except(array $except)
    {
        $req = $this->request;

        foreach ($except as $item) {
            if (isset($req[$item])) {
                unset($req[$item]);
            }
        }

        return $req;
    }
}
