<?php

declare(strict_types=1);

use App\Http\Controllers\BomController;
use App\Http\Controllers\DeliveryController;
use App\Http\Controllers\GeneralJournalController;
use App\Http\Controllers\GeneralLedgerController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\MasterDataController;
use App\Http\Controllers\MasterPriceController;
use App\Http\Controllers\PicGuideController;
use App\Http\Controllers\ProductionController;
use App\Http\Controllers\PurchaseOrderController;
use App\Http\Controllers\ReceivingController;
use App\Http\Controllers\ReturnController;
use App\Http\Controllers\SalesOrderController;
use App\Http\Controllers\StockController;
use App\Http\Controllers\TransactionCancellationController;
use App\Http\Controllers\UserManagementController;
use App\Http\Controllers\WorkOrderController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => auth()->check() ? redirect()->route('dashboard') : view('onboarding'))
    ->name('home');

Route::middleware('auth')->group(function (): void {
    Route::get('/dashboard', [MasterDataController::class, 'dashboard'])->name('dashboard');
    Route::post('/transactions/{type}/{id}/cancel', TransactionCancellationController::class)
        ->whereNumber('id')
        ->name('transactions.cancel');

    Route::get('/pic-guides', [PicGuideController::class, 'index'])
        ->middleware('role:admin')
        ->name('pic-guides.index');
    Route::put('/pic-guides', [PicGuideController::class, 'update'])
        ->middleware('role:admin')
        ->name('pic-guides.update');
    Route::get('/users', [UserManagementController::class, 'index'])
        ->middleware('role:admin')
        ->name('users.index');
    Route::post('/users', [UserManagementController::class, 'store'])
        ->middleware('role:admin')
        ->name('users.store');
    Route::put('/users/{user}/role', [UserManagementController::class, 'updateRole'])
        ->whereNumber('user')
        ->middleware('role:admin')
        ->name('users.role.update');
    Route::put('/users/{user}/access', [UserManagementController::class, 'updateAccess'])
        ->whereNumber('user')
        ->middleware('role:admin')
        ->name('users.access.update');

    Route::prefix('master')->name('master-data.')->middleware('access:master-data')->group(function (): void {
        Route::get('/{master}', [MasterDataController::class, 'index'])->name('index');
        Route::get('/{master}/create', [MasterDataController::class, 'create'])->name('create');
        Route::post('/{master}', [MasterDataController::class, 'store'])->name('store');
        Route::get('/{master}/{record}/edit', [MasterDataController::class, 'edit'])->whereNumber('record')->name('edit');
        Route::put('/{master}/{record}', [MasterDataController::class, 'update'])->whereNumber('record')->name('update');
        Route::delete('/{master}/{record}', [MasterDataController::class, 'destroy'])->whereNumber('record')->name('destroy');
    });

    Route::resource('boms', BomController::class)
        ->except('show')
        ->middleware('access:boms');

    Route::get('/stock/adjustments/{direction}/create', [StockController::class, 'createAdjustment'])
        ->whereIn('direction', ['in', 'out'])
        ->middleware('access:stock')
        ->name('stock.adjustments.create');
    Route::post('/stock/adjustments/{direction}', [StockController::class, 'storeAdjustment'])
        ->whereIn('direction', ['in', 'out'])
        ->middleware('access:stock')
        ->name('stock.adjustments.store');
    Route::get('/stock', [StockController::class, 'index'])
        ->middleware('access:stock')
        ->name('stock.index');
    Route::get('/stock-takes/create', [StockController::class, 'createStockTake'])
        ->middleware('access:stock')
        ->name('stock-takes.create');
    Route::get('/stock-takes/position', [StockController::class, 'stockTakePosition'])
        ->middleware('access:stock')
        ->name('stock-takes.position');
    Route::post('/stock-takes', [StockController::class, 'storeStockTake'])
        ->middleware('access:stock')
        ->name('stock-takes.store');

    Route::get('/purchase-orders/price-lookup', [MasterPriceController::class, 'lookupPurchaseOrderPrice'])
        ->middleware('throttle:60,1')
        ->middleware('access:purchasing')
        ->name('purchase-orders.price');
    Route::resource('purchase-orders', PurchaseOrderController::class)->only([
        'index', 'create', 'store', 'show',
    ])->middleware('access:purchasing');
    Route::get('/receivings', [ReceivingController::class, 'index'])
        ->middleware('access:purchasing')
        ->name('receivings.index');
    Route::get('/receivings/create', [ReceivingController::class, 'create'])
        ->middleware('access:purchasing')
        ->name('receivings.create');
    Route::post('/receivings', [ReceivingController::class, 'store'])
        ->middleware('access:purchasing')
        ->name('receivings.store');
    Route::get('/purchase-returns', [ReturnController::class, 'purchaseIndex'])
        ->middleware('access:purchase-returns')
        ->name('purchase-returns.index');
    Route::get('/purchase-returns/create', [ReturnController::class, 'purchaseCreate'])
        ->middleware('access:purchase-returns')
        ->name('purchase-returns.create');
    Route::post('/purchase-returns', [ReturnController::class, 'storePurchase'])
        ->middleware('access:purchase-returns')
        ->name('purchase-returns.store');

    Route::get('/deliveries', [DeliveryController::class, 'index'])
        ->middleware('access:deliveries')
        ->name('deliveries.index');
    Route::get('/deliveries/create', [DeliveryController::class, 'create'])
        ->middleware(['access:deliveries', 'role:admin,sales'])
        ->name('deliveries.create');
    Route::post('/deliveries', [DeliveryController::class, 'store'])
        ->middleware(['access:deliveries', 'role:admin,sales'])
        ->name('deliveries.store');
    Route::get('/deliveries/{delivery}', [DeliveryController::class, 'show'])
        ->whereNumber('delivery')
        ->middleware('access:deliveries')
        ->name('deliveries.show');
    Route::get('/sales-returns', [ReturnController::class, 'salesIndex'])
        ->middleware('access:sales-returns')
        ->name('sales-returns.index');
    Route::get('/sales-returns/create', [ReturnController::class, 'salesCreate'])
        ->middleware('access:sales-returns')
        ->name('sales-returns.create');
    Route::post('/sales-returns', [ReturnController::class, 'storeSales'])
        ->middleware('access:sales-returns')
        ->name('sales-returns.store');
    Route::post('/deliveries/{delivery}/invoice', [InvoiceController::class, 'storeFromDelivery'])
        ->whereNumber('delivery')
        ->middleware('access:finance')
        ->name('deliveries.invoice.store');
    Route::resource('invoices', InvoiceController::class)
        ->only(['index', 'show'])
        ->middleware('access:finance');
    Route::post('/invoices/{invoice}/payments', [InvoiceController::class, 'storePayment'])
        ->whereNumber('invoice')
        ->middleware('access:finance')
        ->name('invoices.payments.store');
    Route::get('/general-journal', [GeneralJournalController::class, 'index'])
        ->middleware('access:finance')
        ->name('general-journal.index');
    Route::get('/general-journal/create', [GeneralJournalController::class, 'create'])
        ->middleware('access:finance')
        ->name('general-journal.create');
    Route::post('/general-journal', [GeneralJournalController::class, 'store'])
        ->middleware('access:finance')
        ->name('general-journal.store');
    Route::get('/general-journal/{generalJournal}', [GeneralJournalController::class, 'show'])
        ->whereNumber('generalJournal')
        ->middleware('access:finance')
        ->name('general-journal.show');
    Route::get('/general-ledger', [GeneralLedgerController::class, 'index'])
        ->middleware('access:finance')
        ->name('general-ledger.index');

    Route::get('/sales-orders/price-lookup', [MasterPriceController::class, 'lookup'])
        ->middleware('throttle:60,1')
        ->middleware('access:sales-orders')
        ->name('sales-orders.price');
    Route::post('/sales-orders/{salesOrder}/confirm', [SalesOrderController::class, 'confirm'])
        ->whereNumber('salesOrder')
        ->middleware('access:sales-orders')
        ->name('sales-orders.confirm');
    Route::resource('sales-orders', SalesOrderController::class)->only([
        'index', 'create', 'store', 'show', 'edit', 'update',
    ])->middleware('access:sales-orders');

    Route::resource('work-orders', WorkOrderController::class)->only([
        'index', 'create', 'store', 'show',
    ])->middleware('access:production');
    Route::post('/work-orders/{workOrder}/materials/{material}/issue', [ProductionController::class, 'issueMaterial'])
        ->whereNumber(['workOrder', 'material'])
        ->middleware('access:production')
        ->name('work-orders.materials.issue');
    Route::post('/work-orders/{workOrder}/production-entries', [ProductionController::class, 'storeEntry'])
        ->whereNumber('workOrder')
        ->middleware('access:production')
        ->name('work-orders.production-entries.store');
    Route::post('/work-orders/{workOrder}/production-entries/{productionEntry}/inspections', [ProductionController::class, 'storeInspection'])
        ->whereNumber(['workOrder', 'productionEntry'])
        ->middleware('access:production')
        ->name('work-orders.inspections.store');
    Route::post('/work-orders/{workOrder}/qc-inspections/{qcInspection}/receipts', [ProductionController::class, 'storeReceipt'])
        ->whereNumber(['workOrder', 'qcInspection'])
        ->middleware('access:production')
        ->name('work-orders.receipts.store');

    Route::prefix('master-prices/{priceType}')
        ->whereIn('priceType', ['sell', 'buy'])
        ->name('master-prices.')
        ->middleware('access:master-prices')
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
