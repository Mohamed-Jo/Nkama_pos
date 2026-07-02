<?php

use App\Http\Controllers\Admin\RestaurantMesaController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\OperatorPasswordResetController;
use App\Http\Middleware\EnsureShiftOpen;
use App\Http\Controllers\Admin\{
    AuditLogController,
    DashboardController,
    CategoryController,
    ProductController,
    CustomerController,
    SupplierController,
    SaleController,
    PosController,
    OperatorController,
    ModuleController,
    SettingController,
    DocumentSettingController,
    CreditNoteController,
    DirectPrintController,
    ReportController,
    ShiftController,
    CurrentAccountController,
    PurchaseController,
    SystemDateController,
    RestaurantController
};

/*
|--------------------------------------------------------------------------
| Rotas Públicas / Autenticação do Quiosque
|--------------------------------------------------------------------------
*/
Route::get('/', [AuthController::class, 'kiosk']);

Route::get('/kiosk', [AuthController::class, 'kiosk'])->name('kiosk');
Route::post('/pos/auth', [AuthController::class, 'auth'])->name('pos.auth');
Route::post('/pos/logout', [AuthController::class, 'logout'])->name('logout');
Route::get('/operator/password/forgot', [OperatorPasswordResetController::class, 'request'])->name('operator.password.request');
Route::post('/operator/password/reset', [OperatorPasswordResetController::class, 'update'])->name('operator.password.update');


/*
|--------------------------------------------------------------------------
| Painel Administrativo & Operações (Protegido por Middleware)
|--------------------------------------------------------------------------
*/
Route::prefix('admin')->middleware('operator')->name('admin.')->group(function () {

    // Dashboard Centralizado
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::middleware('operator.permission:security.manage')->group(function () {
        Route::post('/system-date/next', [SystemDateController::class, 'next'])->name('system-date.next');
        Route::get('/system-date/daily-report/{date}', [SystemDateController::class, 'downloadDailyReport'])
            ->where('date', '\d{4}-\d{2}-\d{2}')
            ->name('system-date.daily-report');

        Route::resource('operators', OperatorController::class)->only(['index', 'store', 'update']);
        Route::post('operators/{operator}/recovery-code', [OperatorController::class, 'regenerateRecoveryCode'])->name('operators.recovery-code');
        Route::get('settings', [SettingController::class, 'index'])->name('settings.index');
        Route::get('settings/printing-discovery', [SettingController::class, 'printingDiscovery'])->name('settings.printing-discovery');
        Route::put('settings', [SettingController::class, 'update'])->name('settings.update');
        Route::get('document-settings', [DocumentSettingController::class, 'index'])->name('document-settings.index');
        Route::post('document-settings/types', [DocumentSettingController::class, 'storeType'])->name('document-settings.types.store');
        Route::post('document-settings/series', [DocumentSettingController::class, 'storeSeries'])->name('document-settings.series.store');
        Route::patch('document-settings/types/{type}/toggle', [DocumentSettingController::class, 'toggleType'])->name('document-settings.types.toggle');
        Route::patch('document-settings/series/{series}/toggle', [DocumentSettingController::class, 'toggleSeries'])->name('document-settings.series.toggle');
        Route::get('modules', [ModuleController::class, 'index'])->name('modules.index');
        Route::put('modules', [ModuleController::class, 'update'])->name('modules.update');
    });

    Route::middleware('operator.permission:management.view')->group(function () {
        Route::get('/audit', [AuditLogController::class, 'index'])->middleware(['module.enabled:audit', 'operator.permission:audit.view'])->name('audit.index');
        Route::get('reports', [ReportController::class, 'index'])->middleware('operator.permission:reports.view')->name('reports.index');
        Route::get('reports/sales.pdf', [ReportController::class, 'salesPdf'])->middleware('operator.permission:reports.view')->name('reports.sales.pdf');
        Route::get('reports/cash.pdf', [ReportController::class, 'cashPdf'])->middleware('operator.permission:reports.view')->name('reports.cash.pdf');
        Route::get('reports/current-accounts.pdf', [ReportController::class, 'currentAccountsPdf'])
            ->middleware(['module.enabled:current_account', 'operator.permission:current_account.manage'])
            ->name('reports.current-accounts.pdf');
        Route::get('reports/stock.pdf', [ReportController::class, 'stockPdf'])->middleware('operator.permission:reports.view')->name('reports.stock.pdf');
        Route::get('reports/stock-movements.pdf', [ReportController::class, 'stockMovementsPdf'])->middleware('operator.permission:reports.view')->name('reports.stock-movements.pdf');
        Route::get('reports/purchases.pdf', [ReportController::class, 'purchasesPdf'])
            ->middleware(['module.enabled:purchases', 'operator.permission:purchases.create,purchases.approve,purchases.receive'])
            ->name('reports.purchases.pdf');
        Route::get('reports/shifts.pdf', [ReportController::class, 'shiftsPdf'])->middleware('operator.permission:cash.audit')->name('reports.shifts.pdf');
        Route::get('reports/audit.pdf', [ReportController::class, 'auditPdf'])->middleware(['module.enabled:audit', 'operator.permission:audit.view'])->name('reports.audit.pdf');
        Route::get('reports/daily-postings.pdf', [ReportController::class, 'dailyPostingsPdf'])->middleware('operator.permission:reports.view')->name('reports.daily-postings.pdf');
        Route::middleware(['module.enabled:current_account', 'operator.permission:current_account.manage'])->group(function () {
            Route::get('current-accounts', [CurrentAccountController::class, 'index'])->name('current-accounts.index');
            Route::post('current-accounts', [CurrentAccountController::class, 'store'])->name('current-accounts.store');
            Route::post('current-accounts/settle', [CurrentAccountController::class, 'settle'])->name('current-accounts.settle');
        });

        Route::middleware(['module.enabled:purchases', 'operator.permission:purchases.create,purchases.approve,purchases.receive'])->group(function () {
            Route::get('purchases', [PurchaseController::class, 'index'])->name('purchases.index');
        });
        Route::middleware(['module.enabled:purchases', 'operator.permission:purchases.create'])->group(function () {
            Route::get('purchases/create', [PurchaseController::class, 'create'])->name('purchases.create');
            Route::post('purchases', [PurchaseController::class, 'store'])->name('purchases.store');
            Route::patch('purchases/{purchase}/status', [PurchaseController::class, 'updateStatus'])->whereNumber('purchase')->name('purchases.status');
        });
        Route::middleware(['module.enabled:purchases', 'operator.permission:purchases.approve'])->group(function () {
            Route::patch('purchases/{purchase}/approve', [PurchaseController::class, 'approve'])->whereNumber('purchase')->name('purchases.approve');
            Route::patch('purchases/{purchase}/reject', [PurchaseController::class, 'reject'])->whereNumber('purchase')->name('purchases.reject');
        });
        Route::post('purchases/{purchase}/receive', [PurchaseController::class, 'receive'])
            ->whereNumber('purchase')
            ->middleware(['module.enabled:purchases', 'operator.permission:purchases.receive'])
            ->name('purchases.receive');
        Route::get('purchases/{purchase}', [PurchaseController::class, 'show'])
            ->whereNumber('purchase')
            ->middleware(['module.enabled:purchases', 'operator.permission:purchases.create,purchases.approve,purchases.receive'])
            ->name('purchases.show');

        // Cadastros e Recursos Globais
        Route::middleware('operator.permission:catalog.manage')->group(function () {
            Route::resource('categories', CategoryController::class);
            Route::resource('products', ProductController::class);
            Route::resource('customers', CustomerController::class);
            Route::resource('suppliers', SupplierController::class);
        });

        Route::middleware(['module.enabled:restaurant', 'operator.permission:restaurant.manage'])->group(function () {
            Route::get('/mesas', [RestaurantMesaController::class, 'index'])->name('restaurantMesa.index');
            Route::get('/mesas/criar', [RestaurantMesaController::class, 'create'])->name('restaurantMesa.create');
            Route::post('/mesas', [RestaurantMesaController::class, 'store'])->name('restaurantMesa.store');
            Route::post('/mesas/{table}/toggle', [RestaurantMesaController::class, 'toggleStatus'])->name('restaurantMesa.toggle');
        });

        Route::middleware('operator.permission:cash.audit')->group(function () {
            Route::get('shifts/history', [ShiftController::class, 'history'])->name('shifts.history');
            Route::get('shifts/history/{id}', [ShiftController::class, 'show'])->name('shifts.show');
            Route::post('print/shifts/{shift}', [DirectPrintController::class, 'shift'])->name('print.shifts');
        });
    });

    // API para carregar subcategorias dinamicamente
    Route::get('categories/{id}/subcategories', [CategoryController::class, 'subcategories'])
        ->middleware('operator.permission:catalog.manage')
        ->name('categories.subcategories');

    // Ponto de Venda (POS Standard / Supermercado)
    Route::middleware('operator.permission:pos.use')->group(function () {
        Route::get('pos', [PosController::class, 'index'])->name('pos.index');
        Route::post('pos/add', [PosController::class, 'add'])->name('pos.add');
        Route::post('pos/checkout', [PosController::class, 'checkout'])->middleware(EnsureShiftOpen::class)->name('pos.checkout');
        Route::post('pos/supermercado/find-product', [PosController::class, 'findProductByBarcode'])->middleware('module.enabled:supermarket')->name('pos.supermarket.findProduct');
    });

    // Vendas e Histórico
    Route::middleware('operator.permission:sales.credit_note')->group(function () {
        Route::get('sales/{sale}/credit-note', [CreditNoteController::class, 'create'])->name('sales.credit-notes.create');
        Route::post('sales/{sale}/credit-note', [CreditNoteController::class, 'store'])->name('sales.credit-notes.store');
    });
    Route::middleware('operator.permission:sales.view')->group(function () {
        Route::get('credit-notes/{creditNote}/ticket', [CreditNoteController::class, 'ticket'])->name('credit-notes.ticket');
        Route::get('sales/{sale}/ticket', [SaleController::class, 'ticket'])->name('sales.ticket');
        Route::post('print/sales/{sale}', [DirectPrintController::class, 'sale'])->name('print.sales');
        Route::post('print/credit-notes/{creditNote}', [DirectPrintController::class, 'creditNote'])->name('print.credit-notes');
        Route::resource('sales', SaleController::class)->only(['index', 'show']);
    });
    Route::post('sales', [SaleController::class, 'store'])->middleware('operator.permission:sales.create')->name('sales.store');
    // No teu routes/web.php ou api.php
    // Gestão de Turnos / Caixa (Shift)
    Route::middleware('operator.permission:cash.operate')->group(function () {
        Route::post('shift/open', [ShiftController::class, 'openShift'])->name('shift.open');
        Route::post('close-shift', [ShiftController::class, 'closeShift'])->name('shift.close');
        Route::get('current/shift', [ShiftController::class, 'currentShift'])->name('shift.current');
        Route::get('shift/summary', [ShiftController::class, 'summary'])->name('shift.summary');
    });
    // 🍽️ Módulo de Restaurante / Mesas
// 🍽️ Módulo de Restaurante / Mesas
    Route::prefix('restaurant')->name('restaurant.')->middleware(['module.enabled:restaurant', 'operator.permission:restaurant.operate,pos.use'])->group(function () {
        Route::get('/', [RestaurantController::class, 'index'])->name('index');
        Route::get('tables-state', [RestaurantController::class, 'getTablesState'])->name('tablesState');

        // Rotas de controle de Mesa e Pedidos
        Route::post('order/{tableId}/open', [RestaurantController::class, 'openTable'])->middleware(EnsureShiftOpen::class)->name('openTable');
        Route::get('table/{tableId}/summary', [RestaurantController::class, 'tableSummary'])->name('tableSummary');
        Route::get('table/{tableId}/ticket', [RestaurantController::class, 'tableTicket'])->name('tableTicket');
        Route::post('print/table/{table}', [DirectPrintController::class, 'table'])->name('print.table');
        Route::post('transfer-order', [RestaurantController::class, 'transferOrder'])->middleware(EnsureShiftOpen::class)->name('transferOrder');
        Route::post('order/{tableId}/close', [RestaurantController::class, 'closeOrder'])->name('closeOrder');

        // Gestão de itens na mesa
        Route::post('add-item', [RestaurantController::class, 'addItem'])->middleware(EnsureShiftOpen::class)->name('addItem');

        // Rota Corrigida (Caminho limpo e nome do método correto)
        Route::post('remove-item', [RestaurantController::class, 'removeItem'])->name('removeItem');
        Route::post('clear-cart', [RestaurantController::class, 'clearCart'])->name('clearCart');
    });
    // Rotas de MesasRoute [admin.restaurant.create] not defined.


    // Logout Interno do Operador
    Route::post('logout-operator', function () {
        AuditLogger::log('logout', 'Operator', session('operator_id'), [
            'operator_name' => session('operator_name'),
        ]);

        session()->forget('operator_id');
        return response()->json(['success' => true]);
    })->name('logout-operator');

});
