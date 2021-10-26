<?php

namespace Core\Support;

use Closure;
use Core\Http\Request;

interface MiddlewareInterface
{
    public function handle(Request $request, Closure $next);
}
