<?php

use App\Http\Controllers\Admin\Plans\PremiumController;
use App\Http\Controllers\Shopify\DashboardController;
use App\Http\Controllers\Shopify\ThemeInjectionController;
use Illuminate\Http\Request;
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


Route::post('/webhook/customers/data-request', [\App\Http\Controllers\WebhookController::class, 'handleCustomerDataRequest']);
Route::post('/webhook/customers/redact', [\App\Http\Controllers\WebhookController::class, 'handleCustomerRedact']);
Route::post('/webhook/shop/redact', [\App\Http\Controllers\WebhookController::class, 'handleShopRedact']);

// Serve the React app
Route::middleware(['verify.shopify', App\Http\Middleware\Billable::class])->group(function () {
    Route::get('/{any?}', function (Request $request) {
        $host = $request->query('host');
        $shop = $request->query('shop');

        return view('app', compact('host', 'shop'));
    })->where('any', '.*')->name('home');
});
