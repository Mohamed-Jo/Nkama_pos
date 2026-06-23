<?php

use App\Http\Controllers\Admin\RestaurantMesaController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Admin\{
    DashboardController,
    CategoryController,
    ProductController,
    CustomerController,
    SupplierController,
    SaleController,
    PosController,
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


/*
|--------------------------------------------------------------------------
| Painel Administrativo & Operações (Protegido por Middleware)
|--------------------------------------------------------------------------
*/
Route::prefix('admin')->middleware('operator')->name('admin.')->group(function () {

    // Dashboard Centralizado
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Cadastros e Recursos Globais
    Route::resource('categories', CategoryController::class);
    Route::resource('products', ProductController::class);
    Route::resource('customers', CustomerController::class);
    Route::resource('suppliers', SupplierController::class);

    // API para carregar subcategorias dinamicamente
    Route::get('categories/{id}/subcategories', [CategoryController::class, 'subcategories'])->name('categories.subcategories');

    // Ponto de Venda (POS Standard / Supermercado)
    Route::get('pos', [PosController::class, 'index'])->name('pos.index');
    Route::post('pos/add', [PosController::class, 'add'])->name('pos.add');
    Route::post('pos/checkout', [PosController::class, 'checkout'])->name('pos.checkout');
    Route::post('pos/supermercado/find-product', [PosController::class, 'findProductByBarcode'])->name('pos.supermarket.findProduct');

    // Vendas e Histórico
    Route::resource('sales', SaleController::class)->only(['index', 'show']);
    Route::post('sales', [SaleController::class, 'store'])->name('sales.store');
    // No teu routes/web.php ou api.php
    // Gestão de Turnos / Caixa (Shift)
    Route::post('shift/open', [ShiftController::class, 'openShift'])->name('shift.open');
    Route::post('close-shift', [ShiftController::class, 'closeShift'])->name('shift.close');
    Route::get('current/shift', [ShiftController::class, 'currentShift'])->name('shift.current');
    Route::get('shift/summary', [ShiftController::class, 'summary'])->name('shift.summary');
    Route::get('shifts/history', [ShiftController::class, 'history'])->name('shifts.history');
    Route::get('shifts/history/{id}', [ShiftController::class, 'show'])->name('shifts.show');

    // 🍽️ Módulo de Restaurante / Mesas
// 🍽️ Módulo de Restaurante / Mesas
    Route::prefix('restaurant')->name('restaurant.')->group(function () {
        Route::get('/', [RestaurantController::class, 'index'])->name('index');

        // Rotas de controle de Mesa e Pedidos
        Route::post('order/{tableId}/open', [RestaurantController::class, 'openTable'])->name('openTable');
        Route::post('order/{tableId}/close', [RestaurantController::class, 'closeOrder'])->name('closeOrder');

        // Gestão de itens na mesa
        Route::post('add-item', [RestaurantController::class, 'addItem'])->name('addItem');

        // Rota Corrigida (Caminho limpo e nome do método correto)
        Route::post('remove-item', [RestaurantController::class, 'removeItem'])->name('removeItem');
    });
    // Rotas de MesasRoute [admin.restaurant.create] not defined.


    Route::get('/mesas', [RestaurantMesaController::class, 'index'])->name('restaurantMesa.index');
    Route::get('/mesas/criar', [RestaurantMesaController::class, 'create'])->name('restaurantMesa.create');
    Route::post('/mesas', [RestaurantMesaController::class, 'store'])->name('restaurantMesa.store');
    Route::post('/mesas/{table}/toggle', [RestaurantMesaController::class, 'toggleStatus'])->name('restaurantMesa.toggle');
    
    // Logout Interno do Operador
    Route::post('logout-operator', function () {
        session()->forget('operator_id');
        return response()->json(['success' => true]);
    })->name('logout-operator');

});