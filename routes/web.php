<?php

declare(strict_types=1);

use App\Http\Controllers\BomController;
use App\Http\Controllers\MasterDataController;
use App\Http\Controllers\MasterPriceController;
use App\Http\Controllers\PicGuideController;
use App\Http\Controllers\PurchaseOrderController;
use App\Http\Controllers\SalesOrderController;
use App\Http\Controllers\StockController;
use App\Http\Controllers\WorkOrderController;
use Illuminate\Support\Facades\Route;

Route::get('/', [MasterDataController::class, 'dashboard'])->name('dashboard');

Route::get('/pic-guides', [PicGuideController::class, 'index'])->name('pic-guides.index');
Route::put('/pic-guides', [PicGuideController::class, 'update'])->name('pic-guides.update');

Route::prefix('master')->name('master-data.')->group(function (): void {
    Route::get('/{master}', [MasterDataController::class, 'index'])->name('index');
    Route::get('/{master}/create', [MasterDataController::class, 'create'])->name('create');
    Route::post('/{master}', [MasterDataController::class, 'store'])->name('store');
    Route::get('/{master}/{record}/edit', [MasterDataController::class, 'edit'])->whereNumber('record')->name('edit');
    Route::put('/{master}/{record}', [MasterDataController::class, 'update'])->whereNumber('record')->name('update');
    Route::delete('/{master}/{record}', [MasterDataController::class, 'destroy'])->whereNumber('record')->name('destroy');
});

Route::resource('boms', BomController::class)->except('show');

Route::get('/stock/adjustments/{direction}/create', [StockController::class, 'createAdjustment'])
    ->whereIn('direction', ['in', 'out'])
    ->name('stock.adjustments.create');
Route::post('/stock/adjustments/{direction}', [StockController::class, 'storeAdjustment'])
    ->whereIn('direction', ['in', 'out'])
    ->name('stock.adjustments.store');
Route::get('/stock', [StockController::class, 'index'])->name('stock.index');

Route::post('/purchase-orders/{purchaseOrder}/receipts', [PurchaseOrderController::class, 'storeReceipt'])
    ->whereNumber('purchaseOrder')
    ->name('purchase-orders.receipts.store');
Route::get('/purchase-orders/price-lookup', [MasterPriceController::class, 'lookupPurchaseOrderPrice'])
    ->middleware('throttle:60,1')
    ->name('purchase-orders.price');
Route::resource('purchase-orders', PurchaseOrderController::class)->only([
    'index', 'create', 'store', 'show',
]);

Route::get('/sales-orders/price-lookup', [MasterPriceController::class, 'lookup'])
    ->middleware('throttle:60,1')
    ->name('sales-orders.price');
Route::post('/sales-orders/{salesOrder}/confirm', [SalesOrderController::class, 'confirm'])
    ->whereNumber('salesOrder')
    ->name('sales-orders.confirm');
Route::resource('sales-orders', SalesOrderController::class)->only([
    'index', 'create', 'store', 'show', 'edit', 'update',
]);

Route::resource('work-orders', WorkOrderController::class)->only([
    'index', 'create', 'store', 'show',
]);

Route::resource('master-prices', MasterPriceController::class)->only([
    'index', 'create', 'store', 'edit', 'update', 'destroy',
]);
