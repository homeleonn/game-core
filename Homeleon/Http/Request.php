<?php

namespace Homeleon\Http;

use Closure;
use Exception;
use Homeleon\Support\Str;
use Homeleon\Validation\Validator;
use Homeleon\Contracts\Session\Session;
use Homeleon\Support\Facades\App;

class Request
{
    private array $request;
    private string $uri;
    private string $method;
    public Closure $routeResolveAction;

    public function __construct(
        public array $server,
        array $request,
        private Session $session,
        private Validator $validator
    )
    {
        $this->method   = $this->server['REQUEST_METHOD'] ?? 'GET';
        $this->uri      = $this->parseUrl();
        $this->request  = $this->sanitizeRequest($request);
    }

    private function parseUrl(): string
    {
        $urlParts = parse_url($this->server['REQUEST_URI']);

        return Str::addStartSlash($urlParts['path'] ?? '/');
    }

    public function getUrl(): string
    {
        return "{$this->server['REQUEST_SCHEME']}://{$this->server['SERVER_NAME']}{$this->server['REQUEST_URI']}";
    }

    public function validate(array $rules)
    {
        if (!empty($errors = $this->validator->validate($this->request, $rules))) {
            $this->session->set('_errors', $errors);
            exit(App::make(Response::class)->redirect()->back()->getContent());
        }

        return true;
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

    public function get($param): mixed
    {
        return $this->request[$param] ?? null;
    }

    public function getUri(): string
    {
        return $this->uri;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function all(): ?array
    {
        return $this->request;
    }

    public function only(array $only): array
    {
        $res = [];

        foreach ($only as $param) {
            $res[$param] = $this->get($param);
        }

        return $res;
    }

    public function except(array $except): ?array
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
