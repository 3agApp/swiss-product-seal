<?php

use App\Http\Controllers\BrandController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\SupplierController;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;

Route::inertia('/', 'welcome', [
    'canRegister' => Features::enabled(Features::registration()),
])->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'dashboard')->name('dashboard');
    Route::resource('suppliers', SupplierController::class)->except(['show']);
    Route::resource('suppliers.brands', BrandController::class)->only(['store', 'update', 'destroy']);
    Route::resource('products', ProductController::class)->except(['show']);
});

require __DIR__.'/settings.php';
