<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IsAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user() && $request->user()->role === 'Admin') {
            return $next($request);
        }

        abort(403, 'Unauthorized access. Only Admins can view this page.');
    }
}