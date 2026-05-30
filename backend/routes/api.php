<?php

use App\Http\Controllers\Api\V1\AuthApiController;
use App\Http\Controllers\Api\V1\CartApiController;
use App\Http\Controllers\Api\V1\CatalogApiController;
use App\Http\Controllers\Api\V1\CatalogController;
use App\Http\Controllers\Api\V1\DiscountApiController;
use App\Http\Controllers\Api\V1\HealthCheckController;
use App\Http\Controllers\Api\V1\OrderApiController;
use App\Http\Controllers\Api\V1\PaymentWebhookController;
use App\Http\Controllers\Api\V1\SearchController;
use App\Http\Controllers\Api\V1\WishlistController;
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
Route::prefix('v1')->middleware('throttle:api')->group(function () {
    // Health check
    Route::get('/health', HealthCheckController::class)
        ->name('api.v1.health');

    // Auth - Fase 3 (público)
    Route::post('/auth/login', [AuthApiController::class, 'login'])
        ->name('api.v1.auth.login');
    Route::post('/auth/register', [AuthApiController::class, 'register'])
        ->name('api.v1.auth.register');
    Route::post('/auth/refresh', [AuthApiController::class, 'refresh'])
        ->name('api.v1.auth.refresh');

    // Catalog: productos activos (consumido por el frontend e-commerce)
    Route::get('/catalog/products', [CatalogController::class, 'products'])
        ->name('api.v1.catalog.products');

    // Catalog: productos destacados
    Route::get('/catalog/featured', [CatalogController::class, 'featured'])
        ->name('api.v1.catalog.featured');

    // Catalog API (externo) - Fase 1
    Route::get('/catalog/products/{id}', [CatalogApiController::class, 'product'])
        ->name('api.v1.catalog.product.show');

    // Catalog API (externo) - Fase 2
    Route::get('/catalog/categories', [CatalogApiController::class, 'categories'])
        ->name('api.v1.catalog.categories');

    // Payment webhook (público - notificación externa)
    Route::post('/payments/webhook', PaymentWebhookController::class)
        ->withoutMiddleware('throttle:api')
        ->name('api.v1.payments.webhook');

    // Search - Fase 5 (público)
    Route::post('/catalog/search', SearchController::class)
        ->name('api.v1.catalog.search');

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
        Route::get('/cart', [CartApiController::class, 'show'])
            ->name('api.v1.cart.show');
        Route::post('/cart', CartApiController::class)
            ->name('api.v1.cart');

        // Wishlist - Fase 6 (protegido)
        Route::get('/wishlist', [WishlistController::class, 'index'])
            ->name('api.v1.wishlist.index');
        Route::post('/wishlist', [WishlistController::class, 'store'])
            ->name('api.v1.wishlist.store');
        Route::delete('/wishlist/{id}', [WishlistController::class, 'destroy'])
            ->name('api.v1.wishlist.destroy');

        // Discount - Fase 10 (protegido)
        Route::post('/discounts/validate', [DiscountApiController::class, 'validate'])
            ->name('api.v1.discounts.validate');
    });
});

/**
 * Ruta base /api sin version: redirecciona a la version estable actual.
 */
Route::redirect('/', '/api/v1', 301);
