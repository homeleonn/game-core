<?php

namespace Core\Http;

use Core\Support\Facades\Router;

class Response
{

    private $content;

    public function setStatusCode(int $code)
    {
        http_response_code($code);
    }

    public function getContent()
    {
        return $this->content;
    }

    public function setContent($content)
    {
        $this->content = $content;
    }

    public function __invoke($request)
    {
        return ($request->routeResolveAction)();
    }

    public function fire()
    {
        return fn($request) => ($request->routeResolveAction)();
    }

    public function redirect($uri = null, int $statusCode = 302)
    {
        $this->setStatusCode($statusCode);

        return $this;
    }

    public function route(string $name)
    {
        $this->setRedirect(\route($name));

        return $this;
    }

    public function setRedirect($url)
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
