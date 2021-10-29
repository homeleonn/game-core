<?php

namespace Core\Session\Middleware;

use Closure;
use Core\Support\Facades\Session;
use Core\Http\Request;
use Core\Support\MiddlewareInterface;

class StartSession implements MiddlewareInterface
{
    public function handle(Request $request, Closure $next)
    {
        global $errors;
        $errors = [];

        $sessionStart = true;

        if (isset($request->server['HTTP_REFERER'])) {
            $parsedReferer = parse_url($request->server['HTTP_REFERER']);
            if ($parsedReferer['host'] != $request->server['HTTP_HOST'])  {
                $sessionStart = false;
            }
        }

        if ($sessionStart) {
            Session::start();
            $errors = Session::get('_errors');
        }

        $response = $next($request);

        if ($sessionStart) {
            Session::del('_flash');
            Session::del('_errors');
        }

        return $response;
    }
}
