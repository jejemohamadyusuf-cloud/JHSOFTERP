<?php

declare(strict_types=1);

use App\Http\Controllers\BomController;
use App\Http\Controllers\MasterDataController;
use App\Http\Controllers\MasterPriceController;
use App\Http\Controllers\SalesOrderController;
use Illuminate\Support\Facades\Route;

Route::get('/', [MasterDataController::class, 'dashboard'])->name('dashboard');

Route::prefix('master')->name('master-data.')->group(function (): void {
    Route::get('/{master}', [MasterDataController::class, 'index'])->name('index');
    Route::get('/{master}/create', [MasterDataController::class, 'create'])->name('create');
    Route::post('/{master}', [MasterDataController::class, 'store'])->name('store');
    Route::get('/{master}/{record}/edit', [MasterDataController::class, 'edit'])->whereNumber('record')->name('edit');
    Route::put('/{master}/{record}', [MasterDataController::class, 'update'])->whereNumber('record')->name('update');
    Route::delete('/{master}/{record}', [MasterDataController::class, 'destroy'])->whereNumber('record')->name('destroy');
});

Route::resource('boms', BomController::class)->except('show');

Route::get('/sales-orders/price-lookup', [MasterPriceController::class, 'lookup'])
    ->middleware('throttle:60,1')
    ->name('sales-orders.price');
Route::post('/sales-orders/{salesOrder}/confirm', [SalesOrderController::class, 'confirm'])
    ->whereNumber('salesOrder')
    ->name('sales-orders.confirm');
Route::resource('sales-orders', SalesOrderController::class)->only([
    'index', 'create', 'store', 'show', 'edit', 'update',
]);

Route::resource('master-prices', MasterPriceController::class)->only([
    'index', 'create', 'store', 'edit', 'update', 'destroy',
]);
