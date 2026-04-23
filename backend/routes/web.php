<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DiscountController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\SubcategoryController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index']);

Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::middleware(['auth'])->prefix('admin')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('admin.dashboard');

    Route::get('orders', function () {
        return view('admin.orders.index');
    })->name('admin.orders');
    Route::get('customers', function () {
        return view('admin.customers.index');
    })->name('admin.customers');
    Route::get('settings', function () {
        return view('admin.settings.index');
    })->name('admin.settings');

    Route::resource('categories', CategoryController::class)->names('admin.categories');
    Route::resource('subcategories', SubcategoryController::class)->names('admin.subcategories');
    Route::resource('products', ProductController::class)->names('admin.products');
    Route::resource('users', UserController::class)->names('admin.users');
    Route::resource('discounts', DiscountController::class)->names('admin.discounts');
});
