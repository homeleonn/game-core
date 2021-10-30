<?php

namespace Homeleon\Http;

use Closure;
use Homeleon\Support\Facades\Config;
use Homeleon\Support\Facades\Session;

class Response
{
    private $content;

    public function setStatusCode(int $code)
    {
        http_response_code($code);
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function setContent(string $content)
    {
        $this->content = $content;
    }

    public function fire(): Closure
    {
        return fn($request) => ($request->routeResolveAction)();
    }

    public function redirect($uri = null, int $statusCode = 302): self
    {
        $this->setStatusCode($statusCode);

        return $this;
    }

    public function route(string $name): self
    {
        $this->setRedirect(\route($name));

        return $this;
    }

    public function back(): self
    {
        $back = Session::get('_previous')['url'] ?? request()->getUri();
        $this->setRedirect($back);

        return $this;
    }

    public function with($key, $value)
    {
        Session::flash($key, $value);

        return $this;
    }

    public function setRedirect($url): void
    {
        $this->setContent('<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Redirect...</title>
    <meta http-equiv="refresh" content="0; URL='.$url.'" />
</head>
<body></body>
</html>');
    }
}
