<?php

namespace Homeleon\Session\Middleware;

use Closure;
use Homeleon\Support\Facades\Session;
use Homeleon\Http\Request;
use Homeleon\Http\Response;
use Homeleon\Support\MiddlewareInterface;

class StartSession implements MiddlewareInterface
{
    public function handle(Request $request, Closure $next)
    {
        global $errors;
        $errors = [];

        $sessionStart = true;

        // Prevent creating session file for development mode
        if (isDev() && isset($request->server['HTTP_REFERER']) && $request->server['HTTP_REFERER'] == 'http://localhost:8080/') {
            $sessionStart = false;
        }

        if ($sessionStart) {
            Session::start();
            $errors = Session::get('_errors');
        }

        $response = $next($request);

        if ($sessionStart && !($response instanceof (Response::class) && $response->isRedirect())) {
            Session::del('_flash');
            Session::del('_errors');
            Session::del('_old');
        }

        return $response;
    }
}
