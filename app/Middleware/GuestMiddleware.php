<?php

namespace App\Middleware;

use Closure;
use Homeleon\Http\Request;
use Homeleon\Support\Facades\Auth;
use Homeleon\Support\MiddlewareInterface;

class GuestMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, Closure $next)
    {
        if (Auth::check()) {
            return redirect()->route('main');
        }

        return $next($request);
    }
}
