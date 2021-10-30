<?php

namespace Homeleon\Support;

use Closure;
use Homeleon\Http\Request;

interface MiddlewareInterface
{
    public function handle(Request $request, Closure $next);
}
