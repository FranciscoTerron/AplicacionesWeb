<?php

namespace App\Http\Middleware\Api;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ForceJsonResponseMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($response instanceof JsonResponse || $response instanceof RedirectResponse) {
            return $response;
        }

        if ($response instanceof Response) {
            $content = $response->getContent();
            if (! empty($content)) {
                $response->headers->set('Content-Type', 'application/json');
            }
        }

        return $response;
    }
}
