<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AdminMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if (auth('api')->check() && auth('api')->user()->role === 'admin') {
            return $next($request);
        }
        return response()->json(['error' => 'Forbidden'], 403);
    }
}
