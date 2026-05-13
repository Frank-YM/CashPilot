<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmpresaController;
use App\Http\Controllers\CuentaController;
use App\Http\Controllers\CategoriaController;
use App\Http\Controllers\MovimientoController;
use App\Http\Controllers\TransferenciaController;

Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

// API Routes
Route::prefix('api')->group(function () {
    // Dashboard data
    Route::get('/dashboard', [DashboardController::class, 'index']);
    
    // Empresas
    Route::get('/empresas', [EmpresaController::class, 'index']);
    Route::get('/empresas/select', [EmpresaController::class, 'select']);
    Route::post('/empresas', [EmpresaController::class, 'store']);
    Route::put('/empresas/{id}', [EmpresaController::class, 'update']);
    Route::get('/empresas/{id}', [EmpresaController::class, 'show']);
    Route::delete('/empresas/{id}', [EmpresaController::class, 'destroy']);
    
    // Cuentas
    Route::get('/cuentas', [CuentaController::class, 'index']);
    Route::get('/cuentas/select', [CuentaController::class, 'select']);
    Route::post('/cuentas', [CuentaController::class, 'store']);
    Route::put('/cuentas/{id}', [CuentaController::class, 'update']);
    Route::get('/cuentas/{id}', [CuentaController::class, 'show']);
    Route::delete('/cuentas/{id}', [CuentaController::class, 'destroy']);
    
    // Categorías
    Route::get('/categorias', [CategoriaController::class, 'index']);
    Route::get('/categorias/select', [CategoriaController::class, 'select']);
    Route::post('/categorias', [CategoriaController::class, 'store']);
    Route::put('/categorias/{id}', [CategoriaController::class, 'update']);
    Route::delete('/categorias/{id}', [CategoriaController::class, 'destroy']);
    
    // Movimientos
    Route::get('/movimientos', [MovimientoController::class, 'index']);
    Route::post('/movimientos', [MovimientoController::class, 'store']);
    Route::put('/movimientos/{id}', [MovimientoController::class, 'update']);
    Route::get('/movimientos/{id}', [MovimientoController::class, 'show']);
    Route::delete('/movimientos/{id}', [MovimientoController::class, 'destroy']);
    Route::get('/movimientos/saldo/{cuenta_id}', [MovimientoController::class, 'ultimoSaldo']);
    
    // Transferencias
    Route::get('/transferencias', [TransferenciaController::class, 'index']);
    Route::post('/transferencias', [TransferenciaController::class, 'store']);
    Route::get('/transferencias/{id}', [TransferenciaController::class, 'show']);
    Route::delete('/transferencias/{id}', [TransferenciaController::class, 'destroy']);
});