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

        // if (isset($request->server['HTTP_REFERER'])) {
        //     $parsedReferer = parse_url($request->server['HTTP_REFERER']);
        //     if (isset($parsedReferer['port']) && $parsedReferer['port'] != '80') $parsedReferer['host'] .= ':' . $parsedReferer['port'];
        //     if ($parsedReferer['host'] != $request->server['HTTP_HOST'])  {
        //         $sessionStart = false;
        //     }
        // }

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
