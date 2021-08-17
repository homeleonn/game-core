<?php

namespace App\Middleware;

use Core\Request;
use Core\Facades\Auth;
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