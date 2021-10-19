<?php

namespace App\Middleware;

use Core\Http\Request;
use Core\Support\Facades\Auth;
use Closure;

class AuthMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if (!Auth::check()) {
            return redirect()->route('entry');
        }

        return $next($request);
    }
}
