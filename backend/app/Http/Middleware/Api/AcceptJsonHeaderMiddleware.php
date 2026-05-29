<?php

namespace App\Http\Middleware\Api;

use Closure;
use Illuminate\Http\Request;

class AcceptJsonHeaderMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        // Solo aplicar a rutas API (no a docs ni rutas web)
        if ($request->is('api/*') && ! $request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => 'This endpoint only accepts application/json requests.',
            ], 415);
        }

        return $next($request);
    }
}
