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
        Session::start();

        return $next($request);
    }
}
