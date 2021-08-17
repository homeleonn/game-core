<?php

namespace App\Middleware;

use Core\Request;
use Core\Facades\Auth;
use Closure;

class GuestMiddleware
{
	public function handle(Request $request, Closure $next)
	{
		if (Auth::check()) {
			return redirect()->route('main');
		}

		return $next($request);
	}
}