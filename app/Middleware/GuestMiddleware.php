<?php

namespace App\Middleware;

use Closure;
use Core\Http\Request;
use Core\Support\Facades\Auth;
use Core\Support\MiddlewareInterface;

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
