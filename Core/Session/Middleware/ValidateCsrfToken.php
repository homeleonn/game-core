<?php

namespace Core\Session\Middleware;

use Core\Support\Facades\Config;
use Closure;
use Core\Support\Facades\Session;
use Core\Http\Request;
use Core\Support\MiddlewareInterface;

class ValidateCsrfToken implements MiddlewareInterface
{
    public function handle(Request $request, Closure $next)
    {
        if (in_array($request->getMethod(), ['POST', 'PUT', 'DELETE'])) {
            if ($request->get('_token') != Config::get('_token')) {
                return redirect()->back();
            }
        } else {
            Session::set('_previous', [
                'url' => $request->getUrl()
            ]);
        }

        Session::set('_token', Config::get('csrf_token'));

        return $next($request);
    }
}
