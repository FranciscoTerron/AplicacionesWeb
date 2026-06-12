<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ShareSettingsMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        // Valores por defecto - no intentar Firestore en local sin conexión
        $settings = [
            'store_name' => 'MA Piscinas',
            'contact_email' => 'contacto@mapiscinas.com',
            'description' => 'E-commerce de piscinas e insumos de mantenimiento',
        ];

        view()->share('settings', $settings);

        return $next($request);
    }
}
