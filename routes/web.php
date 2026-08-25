<?php

use App\Http\Controllers\AlmacenController;
use App\Http\Controllers\CajaController;
use App\Http\Controllers\CompraController;
use App\Http\Controllers\GastoController;
use App\Http\Controllers\InventarioAjusteController;
use App\Http\Controllers\ProductoController;
use App\Http\Controllers\ProductoPresentacionController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReporteController;
use App\Http\Controllers\ReservaController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SucursalController;
use App\Http\Controllers\TraspasoController;
use App\Http\Controllers\VentaController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware(['auth', 'verified', 'horario'])->group(function () {
    // Reservas es la página de aterrizaje: visible para cualquier usuario autenticado,
    // sin importar sus permisos de módulo (reemplaza al antiguo Dashboard).
    Route::get('/reservas', [ReservaController::class, 'index'])->name('reservas');
    Route::post('/reservas', [ReservaController::class, 'store'])->name('reservas.store');
    Route::post('/reservas/{reserva}/cancelar', [ReservaController::class, 'cancelar'])->name('reservas.cancelar');
    Route::post('/prendas/{prenda}/actualizar', [ReservaController::class, 'actualizarPrenda'])->name('prendas.actualizar');

    Route::middleware('permiso:almacenes')->group(function () {
        Route::get('/almacenes', [AlmacenController::class, 'index'])->name('almacenes');
        Route::post('/almacenes', [AlmacenController::class, 'store'])->name('almacenes.store');
    });

    Route::middleware('permiso:productos')->group(function () {
        Route::get('/productos', [ProductoController::class, 'index'])->name('productos');
        Route::post('/productos', [ProductoController::class, 'store'])->name('productos.store');
        Route::put('/productos/{producto}', [ProductoController::class, 'update'])->name('productos.update');
        Route::post('/productos/{producto}/precios-locales', [ProductoController::class, 'preciosLocales'])->name('productos.precios-locales');
        Route::get('/productos/carga-inicial', [InventarioAjusteController::class, 'create'])->name('productos.carga-inicial');
        Route::post('/productos/carga-inicial', [InventarioAjusteController::class, 'store'])->name('productos.carga-inicial.store');
        Route::post('/productos/{producto}/presentaciones', [ProductoPresentacionController::class, 'store'])->name('productos.presentaciones.store');
        Route::put('/productos/{producto}/presentaciones/{presentacion}', [ProductoPresentacionController::class, 'update'])->name('productos.presentaciones.update');
        Route::post('/productos/{producto}/presentaciones/{presentacion}/activar', [ProductoPresentacionController::class, 'activar'])->name('productos.presentaciones.activar');
    });

    Route::middleware('permiso:ventas')->group(function () {
        Route::get('/ventas', [VentaController::class, 'index'])->name('ventas');
        Route::post('/ventas', [VentaController::class, 'store'])->name('ventas.store');
    });

    Route::middleware('permiso:traspasos')->group(function () {
        Route::get('/traspasos', [TraspasoController::class, 'index'])->name('traspasos');
        Route::post('/traspasos', [TraspasoController::class, 'store'])->name('traspasos.store');
    });

    Route::middleware('permiso:compras')->group(function () {
        Route::get('/compras', [CompraController::class, 'index'])->name('compras');
        Route::post('/compras', [CompraController::class, 'store'])->name('compras.store');
        Route::post('/compras/{compra}/marcar-pagada', [CompraController::class, 'marcarPagada'])->name('compras.marcar-pagada');
    });

    Route::middleware('permiso:gastos')->group(function () {
        Route::get('/gastos', [GastoController::class, 'index'])->name('gastos');
        Route::post('/gastos', [GastoController::class, 'store'])->name('gastos.store');
    });

    Route::middleware('permiso:caja')->group(function () {
        Route::get('/caja', [CajaController::class, 'index'])->name('caja');
        Route::post('/caja', [CajaController::class, 'store'])->name('caja.store');
    });

    // El cambio de sucursal queda disponible para cualquier usuario autenticado
    // (el selector aparece en el menú para todos, sin importar sus permisos de módulo).
    Route::post('/sucursales/{sucursal}/cambiar', [SucursalController::class, 'cambiar'])->name('sucursales.cambiar');

    Route::middleware('permiso:sucursales.index')->group(function () {
        Route::get('/sucursales', [SucursalController::class, 'index'])->name('sucursales.index');
        Route::post('/sucursales', [SucursalController::class, 'store'])->name('sucursales.store');
        Route::put('/sucursales/{sucursal}', [SucursalController::class, 'update'])->name('sucursales.update');
    });

    Route::middleware('permiso:reporte-ventas')->group(function () {
        Route::get('/reporte-ventas', [ReporteController::class, 'index'])->name('reporte-ventas');
    });

    Route::middleware('permiso:roles')->group(function () {
        Route::get('/roles', [RoleController::class, 'index'])->name('roles');
        Route::post('/roles', [RoleController::class, 'store'])->name('roles.store');
        Route::post('/roles/{usuario}/permisos', [RoleController::class, 'actualizarPermisos'])->name('roles.permisos');
        Route::post('/roles/{usuario}/horario', [RoleController::class, 'actualizarHorario'])->name('roles.horario');
        Route::post('/roles/{usuario}/bloqueo-hoy', [RoleController::class, 'bloqueoHoy'])->name('roles.bloqueo-hoy');
    });
});

Route::middleware(['auth', 'horario'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
