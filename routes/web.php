<?php

use App\Http\Controllers\Admin\Plans\PremiumController;
use App\Http\Controllers\Shopify\DashboardController;
use App\Http\Controllers\Shopify\ThemeInjectionController;
use Illuminate\Support\Facades\Route;

// API routes for data
Route::prefix('api')->name('api.')->middleware(['verify.shopify', App\Http\Middleware\Billable::class])->group(function () {
    Route::get('/locales', [DashboardController::class, 'getLocales']);

    Route::post('/install-price-snippet', [ThemeInjectionController::class, 'installSnippet'])->name('install.snippet');
    Route::post('/remove-price-snippet', [ThemeInjectionController::class, 'removeSnippet'])->name('remove.snippet');


    Route::get('/premium', [PremiumController::class, 'index']);
    Route::post('/premium', [PremiumController::class, 'store']);
    Route::delete('/premium', [PremiumController::class, 'destroy']);
});

// Serve the React app
Route::middleware(['verify.shopify', App\Http\Middleware\Billable::class])->group(function () {
    Route::get('/{any?}', fn() => view('app'))->where('any', '.*')->name('home');
});
