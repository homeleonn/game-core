<?php

namespace Homeleon\Http;

use Closure;
use Homeleon\Session\Session;

class Response
{

    private string $content;
    private ?string $redirectPath = null;

    public function __construct(
        private readonly Session $session
    ){}
    public function setStatusCode(int $code): void
    {
        http_response_code($code);
    }

    public function isRedirect(): ?string
    {
        return $this->redirectPath;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function setContent(string $content): void
    {
        $this->content = $content;
    }

    public function fire(): Closure
    {
        return fn($request) => ($request->routeResolveAction)();
    }

    public function redirect($uri = null, int $statusCode = 302): self
    {
        $this->redirectPath = $uri;
        $this->setStatusCode($statusCode);

        return $this;
    }

    public function route(string $name): self
    {
        $this->redirectPath = \route($name);

        return $this;
    }

    public function back(): self
    {
        $back = $this->session->get('_previous')['url'] ?? request()->getUri();
        $this->redirectPath = $back;

        // $this->setRedirect($back);

        return $this;
    }

    public function with($key, $value): self
    {
        $this->session->flash($key, $value);

        return $this;
    }

    public function setRedirect($url = null): void
    {

        header('Location: ' . ($url ?? $this->redirectPath));
//         $this->setContent('<!DOCTYPE html>
// <html lang="en">
// <head>
//     <meta charset="UTF-8">
//     <title>Redirect...</title>
//     <meta http-equiv="refresh" content="0; URL='.$url.'" />
// </head>
// <body></body>
// </html>');
        exit;
    }
}
