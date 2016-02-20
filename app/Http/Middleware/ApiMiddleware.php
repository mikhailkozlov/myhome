<?php

namespace App\Http\Middleware;

use Closure;

class ApiMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Closure $next
     *
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if ($request->header('API-TOKEN') != env('API_TOKEN', str_random())) {

            return response('Unauthorized.', 401);
        }

        return $next($request);
    }
}
