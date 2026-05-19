<?php

namespace App\Http\Middleware\Api;

use Closure;
use Illuminate\Http\Request;

class AcceptJsonHeaderMiddleware
{
    public function handle(Request $request, Closure $next): mixed
    {
        if (! $request->expectsJson() && ! $request->is('api/*')) {
            return response()->json([
                'success' => false,
                'message' => 'This endpoint only accepts application/json requests.',
            ], 415);
        }

        return $next($request);
    }
}
