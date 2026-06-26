<?php

use App\Http\Controllers\Admin\RestaurantMesaController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\OperatorPasswordResetController;
use App\Http\Middleware\EnsureShiftOpen;
use App\Services\AuditLogger;
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
    ShiftController,
    RestaurantController
};

/*
|--------------------------------------------------------------------------
| Rotas Públicas / Autenticação do Quiosque
|--------------------------------------------------------------------------
*/
Route::get('/', function () {
    return view('kiosk');
});

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

    Route::middleware('operator.role:super_user')->group(function () {
        Route::post('/system-date/next', function () {
            $currentDate = \Illuminate\Support\Facades\Cache::get('system_date', now()->toDateString());
            $nextDate = \Carbon\Carbon::parse($currentDate)->addDay()->toDateString();

            \Illuminate\Support\Facades\Cache::forever('system_date', $nextDate);

            AuditLogger::log('system_date_advanced', 'SystemDate', null, [
                'before' => $currentDate,
                'after' => $nextDate,
            ]);

            return back()->with('success', 'Data do sistema alterada para ' . \Carbon\Carbon::parse($nextDate)->format('d/m/Y'));
        })->name('system-date.next');

        Route::resource('operators', OperatorController::class)->only(['index', 'store', 'update']);
        Route::post('operators/{operator}/recovery-code', [OperatorController::class, 'regenerateRecoveryCode'])->name('operators.recovery-code');
        Route::get('modules', [ModuleController::class, 'index'])->name('modules.index');
        Route::put('modules', [ModuleController::class, 'update'])->name('modules.update');
    });

    Route::middleware('operator.role:super_user,admin,manager')->group(function () {
        Route::get('/audit', [AuditLogController::class, 'index'])->name('audit.index');

        // Cadastros e Recursos Globais
        Route::resource('categories', CategoryController::class);
        Route::resource('products', ProductController::class);
        Route::resource('customers', CustomerController::class);
        Route::resource('suppliers', SupplierController::class);

        Route::middleware('module.enabled:restaurant')->group(function () {
            Route::get('/mesas', [RestaurantMesaController::class, 'index'])->name('restaurantMesa.index');
            Route::get('/mesas/criar', [RestaurantMesaController::class, 'create'])->name('restaurantMesa.create');
            Route::post('/mesas', [RestaurantMesaController::class, 'store'])->name('restaurantMesa.store');
            Route::post('/mesas/{table}/toggle', [RestaurantMesaController::class, 'toggleStatus'])->name('restaurantMesa.toggle');
        });

        Route::get('shifts/history', [ShiftController::class, 'history'])->name('shifts.history');
        Route::get('shifts/history/{id}', [ShiftController::class, 'show'])->name('shifts.show');
    });

    // API para carregar subcategorias dinamicamente
    Route::get('categories/{id}/subcategories', [CategoryController::class, 'subcategories'])->name('categories.subcategories');

    // Ponto de Venda (POS Standard / Supermercado)
    Route::get('pos', [PosController::class, 'index'])->name('pos.index');
    Route::post('pos/add', [PosController::class, 'add'])->name('pos.add');
    Route::post('pos/checkout', [PosController::class, 'checkout'])->middleware(EnsureShiftOpen::class)->name('pos.checkout');
    Route::post('pos/supermercado/find-product', [PosController::class, 'findProductByBarcode'])->middleware('module.enabled:supermarket')->name('pos.supermarket.findProduct');

    // Vendas e Histórico
    Route::resource('sales', SaleController::class)->only(['index', 'show']);
    Route::post('sales', [SaleController::class, 'store'])->name('sales.store');
    // No teu routes/web.php ou api.php
    // Gestão de Turnos / Caixa (Shift)
    Route::post('shift/open', [ShiftController::class, 'openShift'])->name('shift.open');
    Route::post('close-shift', [ShiftController::class, 'closeShift'])->name('shift.close');
    Route::get('current/shift', [ShiftController::class, 'currentShift'])->name('shift.current');
    Route::get('shift/summary', [ShiftController::class, 'summary'])->name('shift.summary');
    // 🍽️ Módulo de Restaurante / Mesas
// 🍽️ Módulo de Restaurante / Mesas
    Route::prefix('restaurant')->name('restaurant.')->middleware('module.enabled:restaurant')->group(function () {
        Route::get('/', [RestaurantController::class, 'index'])->name('index');
        Route::get('tables-state', [RestaurantController::class, 'getTablesState'])->name('tablesState');

        // Rotas de controle de Mesa e Pedidos
        Route::post('order/{tableId}/open', [RestaurantController::class, 'openTable'])->middleware(EnsureShiftOpen::class)->name('openTable');
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
