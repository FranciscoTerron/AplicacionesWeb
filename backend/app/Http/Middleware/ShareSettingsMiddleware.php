<?php

namespace App\Http\Middleware;

use App\Services\FirestoreService;
use Closure;
use Illuminate\Http\Request;

class ShareSettingsMiddleware
{
    protected FirestoreService $firestore;

    public function __construct(FirestoreService $firestore)
    {
        $this->firestore = $firestore;
    }

    public function handle(Request $request, Closure $next)
    {
        $settings = $this->firestore->getDocument('settings', 'store');

        if (! $settings) {
            $settings = [
                'store_name' => 'MA Piscinas',
                'contact_email' => 'contacto@mapiscinas.com',
                'description' => 'E-commerce de piscinas e insumos de mantenimiento',
            ];
        }

        view()->share('settings', $settings);

        return $next($request);
    }
}
