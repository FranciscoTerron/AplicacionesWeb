<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Agrega security headers a todas las respuestas (API JSON + panel admin Blade).
 *
 * Cierra S-7 de la auditoría: sin estos headers el admin quedaba expuesto a
 * clickjacking (sin X-Frame-Options) y sin HSTS.
 *
 * Nota: NO se setea Content-Security-Policy acá porque el panel admin carga
 * Tailwind por CDN (deuda D-5), que exige script-src/style-src permisivos que
 * volverían la CSP inútil. La CSP queda pendiente hasta compilar Tailwind local.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // HSTS solo sobre HTTPS. En Vercel el TLS lo termina el proxy, así que
        // $request->secure() puede dar false; se contempla X-Forwarded-Proto
        // (por eso el proyecto ya usa URL::forceScheme('https') en Vercel).
        $isHttps = $request->secure()
            || strtolower((string) $request->header('X-Forwarded-Proto')) === 'https';

        if ($isHttps) {
            $response->headers->set(
                'Strict-Transport-Security',
                'max-age=31536000; includeSubDomains'
            );
        }

        return $response;
    }
}
