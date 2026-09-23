<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireProSubscription
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->isPro()) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json(['message' => 'A PromptGrove Pro subscription is required.'], 402);
        }

        return redirect()->route('billing.index')->with('error', 'Upgrade to Pro to use that feature.');
    }
}
