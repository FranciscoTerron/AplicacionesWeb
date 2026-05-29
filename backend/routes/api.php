<?php

use App\Http\Controllers\Api\V1\AuthApiController;
use App\Http\Controllers\Api\V1\CartApiController;
use App\Http\Controllers\Api\V1\CatalogApiController;
use App\Http\Controllers\Api\V1\CatalogController;
use App\Http\Controllers\Api\V1\DiscountApiController;
use App\Http\Controllers\Api\V1\HealthCheckController;
use App\Http\Controllers\Api\V1\OrderApiController;
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

    // Auth - Fase 3 (público)
    Route::post('/auth/login', [AuthApiController::class, 'login'])
        ->name('api.v1.auth.login');

    // Catalog: productos activos (consumido por el frontend e-commerce)
    Route::get('/catalog/products', [CatalogController::class, 'products'])
        ->name('api.v1.catalog.products');

    // Catalog API (externo) - Fase 1
    Route::get('/catalog/products/{id}', [CatalogApiController::class, 'product'])
        ->name('api.v1.catalog.product.show');

    // Catalog API (externo) - Fase 2
    Route::get('/catalog/categories', [CatalogApiController::class, 'categories'])
        ->name('api.v1.catalog.categories');

    // Orders - Fase 5-8 (protegido)
    Route::middleware('auth.api')->group(function () {
        Route::post('/orders', [OrderApiController::class, 'store'])
            ->name('api.v1.orders.store');
        Route::get('/orders', [OrderApiController::class, 'index'])
            ->name('api.v1.orders.index');
        Route::get('/orders/{id}', [OrderApiController::class, 'show'])
            ->name('api.v1.orders.show');
        Route::put('/orders/{id}/cancel', [OrderApiController::class, 'cancel'])
            ->name('api.v1.orders.cancel');

        // Cart - Fase 9 (protegido)
        Route::post('/cart', CartApiController::class)
            ->name('api.v1.cart');

        // Discount - Fase 10 (protegido)
        Route::post('/discounts/validate', [DiscountApiController::class, 'validate'])
            ->name('api.v1.discounts.validate');
    });
});

/**
 * Ruta base /api sin version: redirecciona a la version estable actual.
 */
Route::redirect('/', '/api/v1', 301);
