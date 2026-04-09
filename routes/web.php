<?php

use App\Http\Controllers\BrandController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductDocumentController;
use App\Http\Controllers\ProductImageController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\TemplateController;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;

Route::inertia('/', 'welcome', [
    'canRegister' => Features::enabled(Features::registration()),
])->name('home');

Route::middleware(['auth', 'verified'])->scopeBindings()->group(function () {
    Route::inertia('dashboard', 'dashboard')->name('dashboard');
    Route::resource('suppliers', SupplierController::class)->except(['show']);
    Route::resource('suppliers.brands', BrandController::class)->only(['store', 'update', 'destroy']);
    Route::resource('categories', CategoryController::class)->except(['show']);
    Route::resource('templates', TemplateController::class)->except(['show']);
    Route::resource('products', ProductController::class)->except(['show']);
    Route::post('products/{product}/documents', [ProductDocumentController::class, 'store'])->name('products.documents.store');
    Route::post('products/{product}/images', [ProductImageController::class, 'store'])->name('products.images.store');
    Route::delete('products/{product}/images/{media}', [ProductImageController::class, 'destroy'])->name('products.images.destroy');
    Route::put('products/{product}/images/reorder', [ProductImageController::class, 'reorder'])->name('products.images.reorder');
});

require __DIR__.'/settings.php';
