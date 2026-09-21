<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BlockDemoEmailAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_if(config('demo.oauth_only'), 404);

        return $next($request);
    }
}
