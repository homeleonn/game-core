<?php

namespace App\Middleware;

use Closure;
use Homeleon\Http\Request;
use Homeleon\Support\MiddlewareInterface;

class ApiMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, Closure $next)
    {
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
        header('Access-Control-Allow-Headers: X-Requested-With,Authorization,Content-Type');
        header('Content-Type: application/json; charset=utf-8');

        return $next($request);
    }
}
