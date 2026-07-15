<?php

use App\Http\Controllers\Admin\ExportController;
use App\Http\Controllers\Admin\ImportController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DiscountController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\ShipmentController;
use App\Http\Controllers\SubcategoryController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');

Route::get('/productos', [CatalogController::class, 'index'])->name('catalog.index');
Route::get('/productos/{id}', [CatalogController::class, 'show'])->name('catalog.show');

Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::get('/auth/google', [AuthController::class, 'redirectToGoogle'])->name('auth.google');
Route::get('/auth/google/callback', [AuthController::class, 'handleGoogleCallback'])->name('auth.google.callback');

Route::middleware(['auth', 'role:admin,editor'])->prefix('admin')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('admin.dashboard');

    Route::get('settings', [SettingController::class, 'index'])->name('admin.settings');
    Route::put('settings', [SettingController::class, 'update'])->middleware(['admin'])->name('admin.settings.update');

    // Rutas accesibles tanto para editors como para admins (solo lectura)
    Route::resource('categories', CategoryController::class)->names('admin.categories');
    Route::post('categories/{category}/activate', [CategoryController::class, 'activate'])->name('admin.categories.activate');
    Route::resource('subcategories', SubcategoryController::class)->names('admin.subcategories');
    Route::post('subcategories/{subcategory}/activate', [SubcategoryController::class, 'activate'])->name('admin.subcategories.activate');
    Route::resource('products', ProductController::class)->names('admin.products');
    Route::post('products/{product}/activate', [ProductController::class, 'activate'])->name('admin.products.activate');

    // Index para editors y admins (solo lectura).
    // IMPORTANTE: las rutas estáticas (/create, /algo-fijo) deben declararse ANTES que
    // las dinámicas con {id}; si no, el segmento "create" se interpreta como id.
    Route::get('discounts', [DiscountController::class, 'index'])->name('admin.discounts.index');
    Route::get('clients', [ClientController::class, 'index'])->name('admin.clients.index');
    Route::get('orders', [OrderController::class, 'index'])->name('admin.orders.index');
    Route::get('shipments', [ShipmentController::class, 'index'])->name('admin.shipments.index');

    // Rutas solo admin: create + acciones que mutan estado.
    Route::middleware(['admin'])->group(function () {
        // Descuentos
        Route::get('discounts/create', [DiscountController::class, 'create'])->name('admin.discounts.create');
        Route::post('discounts', [DiscountController::class, 'store'])->name('admin.discounts.store');

        // Clientes
        Route::get('clients/create', [ClientController::class, 'create'])->name('admin.clients.create');
        Route::post('clients', [ClientController::class, 'store'])->name('admin.clients.store');

        // Órdenes
        Route::get('orders/create', [OrderController::class, 'create'])->name('admin.orders.create');
        Route::post('orders', [OrderController::class, 'store'])->name('admin.orders.store');

        // Envíos
        Route::get('shipments/create', [ShipmentController::class, 'create'])->name('admin.shipments.create');
        Route::post('shipments', [ShipmentController::class, 'store'])->name('admin.shipments.store');

        // Notificaciones push a la tienda (HU-B13)
        Route::get('notifications', [NotificationController::class, 'index'])->name('admin.notifications.index');
        Route::post('notifications', [NotificationController::class, 'send'])->name('admin.notifications.send');
    });

    // Show para editors y admins (lectura con id dinámico). Va DESPUÉS de /create.
    Route::get('discounts/{discount}', [DiscountController::class, 'show'])->name('admin.discounts.show');
    Route::get('clients/{client}', [ClientController::class, 'show'])->name('admin.clients.show');
    Route::get('orders/{order}', [OrderController::class, 'show'])->name('admin.orders.show');
    Route::get('shipments/{shipment}', [ShipmentController::class, 'show'])->name('admin.shipments.show');

    // Cambio de estado de orden (editor + admin, validado por OrderPolicy::update).
    Route::patch('orders/{order}/status', [OrderController::class, 'updateStatus'])->name('admin.orders.status');

    // Edición/baja/reactivación con id dinámico (solo admin).
    Route::middleware(['admin'])->group(function () {
        // Descuentos
        Route::get('discounts/{discount}/edit', [DiscountController::class, 'edit'])->name('admin.discounts.edit');
        Route::put('discounts/{discount}', [DiscountController::class, 'update'])->name('admin.discounts.update');
        Route::patch('discounts/{discount}', [DiscountController::class, 'update']);
        Route::delete('discounts/{discount}', [DiscountController::class, 'destroy'])->name('admin.discounts.destroy');
        Route::post('discounts/{discount}/activate', [DiscountController::class, 'activate'])->name('admin.discounts.activate');

        // Clientes
        Route::get('clients/{client}/edit', [ClientController::class, 'edit'])->name('admin.clients.edit');
        Route::put('clients/{client}', [ClientController::class, 'update'])->name('admin.clients.update');
        Route::patch('clients/{client}', [ClientController::class, 'update']);
        Route::delete('clients/{client}', [ClientController::class, 'destroy'])->name('admin.clients.destroy');
        Route::post('clients/{client}/activate', [ClientController::class, 'activate'])->name('admin.clients.activate');
        Route::post('clients/{client}/deactivate', [ClientController::class, 'deactivate'])->name('admin.clients.deactivate');

        // Órdenes (sin activate; el ciclo de vida se maneja vía updateStatus arriba)
        Route::get('orders/{order}/edit', [OrderController::class, 'edit'])->name('admin.orders.edit');
        Route::put('orders/{order}', [OrderController::class, 'update'])->name('admin.orders.update');
        Route::patch('orders/{order}', [OrderController::class, 'update']);
        Route::delete('orders/{order}', [OrderController::class, 'destroy'])->name('admin.orders.destroy');

        // Envíos
        Route::get('shipments/{shipment}/edit', [ShipmentController::class, 'edit'])->name('admin.shipments.edit');
        Route::put('shipments/{shipment}', [ShipmentController::class, 'update'])->name('admin.shipments.update');
        Route::patch('shipments/{shipment}', [ShipmentController::class, 'update']);
        Route::delete('shipments/{shipment}', [ShipmentController::class, 'destroy'])->name('admin.shipments.destroy');
        Route::post('shipments/{shipment}/activate', [ShipmentController::class, 'activate'])->name('admin.shipments.activate');
    });

    // Usuarios (ya tiene lógica de autorización interna, pero lo protegemos por las dudas)
    Route::middleware(['admin'])->group(function () {
        Route::resource('users', UserController::class)->names('admin.users');
        Route::post('users/{user}/activate', [UserController::class, 'activate'])->name('admin.users.activate');
    });

    // Exportaciones CSV
    Route::middleware(['auth'])->group(function () {
        Route::get('export/{entity}', [ExportController::class, 'export'])
            ->where('entity', 'categories|subcategories|products|discounts|clients|orders|shipments')
            ->name('admin.export.csv');
    });

    // Importaciones CSV
    Route::middleware(['admin'])->group(function () {
        Route::get('import/{entity}/create', [ImportController::class, 'create'])
            ->where('entity', 'categories|subcategories|products')
            ->name('admin.import.create');
        Route::post('import/{entity}', [ImportController::class, 'store'])
            ->where('entity', 'categories|subcategories|products')
            ->name('admin.import.store');
    });
});
