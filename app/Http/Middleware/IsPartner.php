<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IsPartner
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user() && $request->user()->role === 'Partner') {
            return $next($request);
        }

        abort(403, 'Unauthorized access. Only Partners can access this route.');
    }
}