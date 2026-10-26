<?php

declare(strict_types=1);

use App\Http\Controllers\BomController;
use App\Http\Controllers\DeliveryController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\MasterDataController;
use App\Http\Controllers\MasterPriceController;
use App\Http\Controllers\PicGuideController;
use App\Http\Controllers\ProductionController;
use App\Http\Controllers\PurchaseOrderController;
use App\Http\Controllers\ReceivingController;
use App\Http\Controllers\SalesOrderController;
use App\Http\Controllers\StockController;
use App\Http\Controllers\UserManagementController;
use App\Http\Controllers\WorkOrderController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => auth()->check() ? redirect()->route('dashboard') : view('onboarding'))
    ->name('home');

Route::middleware('auth')->group(function (): void {
    Route::get('/dashboard', [MasterDataController::class, 'dashboard'])->name('dashboard');

    Route::get('/pic-guides', [PicGuideController::class, 'index'])
        ->middleware('role:admin')
        ->name('pic-guides.index');
    Route::put('/pic-guides', [PicGuideController::class, 'update'])
        ->middleware('role:admin')
        ->name('pic-guides.update');
    Route::get('/users', [UserManagementController::class, 'index'])
        ->middleware('role:admin')
        ->name('users.index');
    Route::put('/users/{user}/role', [UserManagementController::class, 'updateRole'])
        ->whereNumber('user')
        ->middleware('role:admin')
        ->name('users.role.update');

    Route::prefix('master')->name('master-data.')->middleware('role:admin,sales,inventory')->group(function (): void {
        Route::get('/{master}', [MasterDataController::class, 'index'])->name('index');
        Route::get('/{master}/create', [MasterDataController::class, 'create'])->name('create');
        Route::post('/{master}', [MasterDataController::class, 'store'])->name('store');
        Route::get('/{master}/{record}/edit', [MasterDataController::class, 'edit'])->whereNumber('record')->name('edit');
        Route::put('/{master}/{record}', [MasterDataController::class, 'update'])->whereNumber('record')->name('update');
        Route::delete('/{master}/{record}', [MasterDataController::class, 'destroy'])->whereNumber('record')->name('destroy');
    });

    Route::resource('boms', BomController::class)
        ->except('show')
        ->middleware('role:admin,inventory,production');

    Route::get('/stock/adjustments/{direction}/create', [StockController::class, 'createAdjustment'])
        ->whereIn('direction', ['in', 'out'])
        ->middleware('role:admin,inventory')
        ->name('stock.adjustments.create');
    Route::post('/stock/adjustments/{direction}', [StockController::class, 'storeAdjustment'])
        ->whereIn('direction', ['in', 'out'])
        ->middleware('role:admin,inventory')
        ->name('stock.adjustments.store');
    Route::get('/stock', [StockController::class, 'index'])
        ->middleware('role:admin,inventory')
        ->name('stock.index');
    Route::get('/stock-takes/create', [StockController::class, 'createStockTake'])
        ->middleware('role:admin,inventory')
        ->name('stock-takes.create');
    Route::get('/stock-takes/position', [StockController::class, 'stockTakePosition'])
        ->middleware('role:admin,inventory')
        ->name('stock-takes.position');
    Route::post('/stock-takes', [StockController::class, 'storeStockTake'])
        ->middleware('role:admin,inventory')
        ->name('stock-takes.store');

    Route::get('/purchase-orders/price-lookup', [MasterPriceController::class, 'lookupPurchaseOrderPrice'])
        ->middleware('throttle:60,1')
        ->middleware('role:admin,inventory')
        ->name('purchase-orders.price');
    Route::resource('purchase-orders', PurchaseOrderController::class)->only([
        'index', 'create', 'store', 'show',
    ])->middleware('role:admin,inventory');
    Route::get('/receivings', [ReceivingController::class, 'index'])
        ->middleware('role:admin,inventory')
        ->name('receivings.index');
    Route::get('/receivings/create', [ReceivingController::class, 'create'])
        ->middleware('role:admin,inventory')
        ->name('receivings.create');
    Route::post('/receivings', [ReceivingController::class, 'store'])
        ->middleware('role:admin,inventory')
        ->name('receivings.store');

    Route::get('/deliveries', [DeliveryController::class, 'index'])
        ->middleware('role:admin,sales,finance')
        ->name('deliveries.index');
    Route::get('/deliveries/create', [DeliveryController::class, 'create'])
        ->middleware('role:admin,sales')
        ->name('deliveries.create');
    Route::post('/deliveries', [DeliveryController::class, 'store'])
        ->middleware('role:admin,sales')
        ->name('deliveries.store');
    Route::get('/deliveries/{delivery}', [DeliveryController::class, 'show'])
        ->whereNumber('delivery')
        ->middleware('role:admin,sales,finance')
        ->name('deliveries.show');
    Route::post('/deliveries/{delivery}/invoice', [InvoiceController::class, 'storeFromDelivery'])
        ->whereNumber('delivery')
        ->middleware('role:admin,finance')
        ->name('deliveries.invoice.store');
    Route::resource('invoices', InvoiceController::class)
        ->only(['index', 'show'])
        ->middleware('role:admin,finance');
    Route::post('/invoices/{invoice}/payments', [InvoiceController::class, 'storePayment'])
        ->whereNumber('invoice')
        ->middleware('role:admin,finance')
        ->name('invoices.payments.store');

    Route::get('/sales-orders/price-lookup', [MasterPriceController::class, 'lookup'])
        ->middleware('throttle:60,1')
        ->middleware('role:admin,sales')
        ->name('sales-orders.price');
    Route::post('/sales-orders/{salesOrder}/confirm', [SalesOrderController::class, 'confirm'])
        ->whereNumber('salesOrder')
        ->middleware('role:admin,sales')
        ->name('sales-orders.confirm');
    Route::resource('sales-orders', SalesOrderController::class)->only([
        'index', 'create', 'store', 'show', 'edit', 'update',
    ])->middleware('role:admin,sales');

    Route::resource('work-orders', WorkOrderController::class)->only([
        'index', 'create', 'store', 'show',
    ])->middleware('role:admin,production');
    Route::post('/work-orders/{workOrder}/materials/{material}/issue', [ProductionController::class, 'issueMaterial'])
        ->whereNumber(['workOrder', 'material'])
        ->middleware('role:admin,production')
        ->name('work-orders.materials.issue');
    Route::post('/work-orders/{workOrder}/production-entries', [ProductionController::class, 'storeEntry'])
        ->whereNumber('workOrder')
        ->middleware('role:admin,production')
        ->name('work-orders.production-entries.store');
    Route::post('/work-orders/{workOrder}/production-entries/{productionEntry}/inspections', [ProductionController::class, 'storeInspection'])
        ->whereNumber(['workOrder', 'productionEntry'])
        ->middleware('role:admin,production')
        ->name('work-orders.inspections.store');
    Route::post('/work-orders/{workOrder}/qc-inspections/{qcInspection}/receipts', [ProductionController::class, 'storeReceipt'])
        ->whereNumber(['workOrder', 'qcInspection'])
        ->middleware('role:admin,production')
        ->name('work-orders.receipts.store');

    Route::prefix('master-prices/{priceType}')
        ->whereIn('priceType', ['sell', 'buy'])
        ->name('master-prices.')
        ->middleware('role:admin,sales,inventory')
        ->group(function (): void {
            Route::get('/', [MasterPriceController::class, 'index'])->name('index');
            Route::get('/create', [MasterPriceController::class, 'create'])->name('create');
            Route::post('/', [MasterPriceController::class, 'store'])->name('store');
            Route::get('/{masterPrice}/edit', [MasterPriceController::class, 'edit'])->whereNumber('masterPrice')->name('edit');
            Route::put('/{masterPrice}', [MasterPriceController::class, 'update'])->whereNumber('masterPrice')->name('update');
            Route::delete('/{masterPrice}', [MasterPriceController::class, 'destroy'])->whereNumber('masterPrice')->name('destroy');
        });
});

require __DIR__.'/auth.php';
