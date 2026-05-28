<?php

use App\Http\Controllers\Api\V1\CatalogController;
use App\Http\Controllers\Api\V1\HealthCheckController;
use Illuminate\Support\Facades\Route;

/**
 * Laravel API Routes
 *
 * Todas las rutas aqui definidas tienen automaticamente el prefijo /api/.
 * Se incluye middleware 'api' (Stateless, withCORS) y el alias 'api' de Laravel.
 *
 * versionado:
 *   - /api/v1/...  => Version 1 (actual)
 *   - /api/v2/...  => Version 2 (futuro)
 *
 * NOTA: Las colecciones Resource son para respuestas Eloquent, pero como
 * usamos Firestore controlamos la serializacion manualmente en cada endpoint.
 */

/**
 * Ruta publica sin autenticacion: health check.
 */
Route::prefix('v1')->group(function () {
    // Health check
    Route::get('/health', HealthCheckController::class)
        ->name('api.v1.health');

    // Catalog: productos activos (consumido por el frontend e-commerce)
    Route::get('/catalog/products', [CatalogController::class, 'products'])
        ->name('api.v1.catalog.products');
});

/**
 * Ruta base /api sin version: redirecciona a la version estable actual.
 */
Route::redirect('/', '/api/v1', 301);
