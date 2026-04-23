<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

Route::get('/', [App\Http\Controllers\HomeController::class, 'index']);

Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::middleware(['auth'])->prefix('admin')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('admin.dashboard');
    
    Route::get('orders', function() { return view('admin.orders.index'); })->name('admin.orders');
    Route::get('customers', function() { return view('admin.customers.index'); })->name('admin.customers');
    Route::get('settings', function() { return view('admin.settings.index'); })->name('admin.settings');

    Route::resource('categories', \App\Http\Controllers\CategoryController::class)->names('admin.categories');
    Route::resource('subcategories', \App\Http\Controllers\SubcategoryController::class)->names('admin.subcategories');
    Route::resource('products', \App\Http\Controllers\ProductController::class)->names('admin.products');
    Route::resource('users', \App\Http\Controllers\UserController::class)->names('admin.users');
    Route::resource('discounts', \App\Http\Controllers\DiscountController::class)->names('admin.discounts');
});