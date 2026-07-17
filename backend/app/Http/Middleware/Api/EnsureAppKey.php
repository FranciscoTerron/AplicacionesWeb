<?php

namespace App\Http\Middleware\Api;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Restringe los endpoints públicos de la API a clientes que envíen la clave
 * compartida en el header X-App-Key (el frontend React).
 *
 * Enforcement opt-in: si APP_PUBLIC_KEY no está configurada (dev/tests), no se
 * exige nada. En producción se setea la env y la API queda restringida.
 *
 * Límite conocido: al ser una SPA pública, la clave viaja en el bundle del
 * front y no es un secreto criptográficamente fuerte. Sube la barrera contra
 * clientes automatizados (curl/bots), no la elimina.
 */
class EnsureAppKey
{
    public function handle(Request $request, Closure $next): Response
    {
        $expected = config('app.public_api_key');

        if (! $expected) {
            return $next($request);
        }

        $provided = $request->header('X-App-Key');
        if (! is_string($provided) || ! hash_equals((string) $expected, $provided)) {
            return response()->json([
                'success' => false,
                'message' => 'Cliente no autorizado.',
            ], 403);
        }

        return $next($request);
    }
}
