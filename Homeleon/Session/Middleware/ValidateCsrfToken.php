<?php

namespace Homeleon\Session\Middleware;

use Homeleon\Support\Facades\Config;
use Closure;
use Homeleon\Support\Facades\Session;
use Homeleon\Http\Request;
use Homeleon\Support\MiddlewareInterface;

class ValidateCsrfToken implements MiddlewareInterface
{
    public function handle(Request $request, Closure $next)
    {
        if (in_array($request->getMethod(), ['POST', 'PUT', 'DELETE'])) {
            if ($request->get('_token') != Session::get('_token')) {
                return redirect()->back();
            }
        } else {
            Session::set('_previous', [
                'url' => $request->getUrl()
            ]);

            Session::set('_token', Config::get('csrf_token'));
        }

        return $next($request);
    }
}
