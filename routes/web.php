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
use App\Http\Controllers\SolicitudReservaWhatsappController;
use App\Http\Controllers\SucursalController;
use App\Http\Controllers\TraspasoController;
use App\Http\Controllers\VentaController;
use App\Http\Controllers\WhatsappChatController;
use App\Http\Controllers\WhatsappController;
use App\Http\Controllers\WhatsappFlujoController;
use Illuminate\Support\Facades\Route;

Route::post('/whatsapp/webhook', [WhatsappController::class, 'webhook'])->name('whatsapp.webhook');

Route::get('/', function () {
    return redirect()->route(auth()->check() ? 'reservas' : 'login');
});

Route::middleware(['auth', 'verified', 'horario'])->group(function () {
    Route::middleware('permiso:reservas')->group(function () {
        Route::get('/reservas', [ReservaController::class, 'index'])->name('reservas');
        Route::post('/reservas', [ReservaController::class, 'store'])->name('reservas.store');
        Route::post('/reservas/{reserva}/cancelar', [ReservaController::class, 'cancelar'])->name('reservas.cancelar');
        Route::put('/reservas/{reserva}', [ReservaController::class, 'actualizar'])->name('reservas.actualizar');
        Route::post('/prendas', [ReservaController::class, 'guardarPrenda'])->name('prendas.store');
        Route::post('/prendas/{prenda}/actualizar', [ReservaController::class, 'actualizarPrenda'])->name('prendas.actualizar');
        Route::delete('/clientes/{cliente}', [ReservaController::class, 'destroyCliente'])->name('clientes.destroy');
    });

    Route::middleware('permiso:almacenes')->group(function () {
        Route::get('/almacenes', [AlmacenController::class, 'index'])->name('almacenes');
        Route::post('/almacenes', [AlmacenController::class, 'store'])->name('almacenes.store');
        Route::put('/almacenes/{almacen}', [AlmacenController::class, 'update'])->name('almacenes.update');
        Route::delete('/almacenes/{almacen}', [AlmacenController::class, 'destroy'])->name('almacenes.destroy');
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
    // (ya no vive en el menú: se elige una vez, justo después de loguearse, desde
    // la pantalla "sucursales.seleccionar").
    Route::get('/seleccionar-sucursal', [SucursalController::class, 'seleccionar'])->name('sucursales.seleccionar');
    Route::post('/sucursales/{sucursal}/cambiar', [SucursalController::class, 'cambiar'])->name('sucursales.cambiar');

    Route::middleware('permiso:sucursales.index')->group(function () {
        Route::get('/sucursales', [SucursalController::class, 'index'])->name('sucursales.index');
        Route::post('/sucursales', [SucursalController::class, 'store'])->name('sucursales.store');
        Route::put('/sucursales/{sucursal}', [SucursalController::class, 'update'])->name('sucursales.update');
    });

    Route::middleware('permiso:reporte-ventas')->group(function () {
        Route::get('/reporte-ventas', [ReporteController::class, 'index'])->name('reporte-ventas');
    });

    Route::middleware('permiso:whatsapp')->group(function () {
        Route::get('/whatsapp', [WhatsappController::class, 'index'])->name('whatsapp');
        Route::get('/whatsapp/estado', [WhatsappController::class, 'estado'])->name('whatsapp.estado');
        Route::post('/whatsapp/conectar', [WhatsappController::class, 'conectar'])->name('whatsapp.conectar');
        Route::post('/whatsapp/desconectar', [WhatsappController::class, 'desconectar'])->name('whatsapp.desconectar');
    });

    Route::middleware('permiso:solicitudes')->group(function () {
        Route::get('/whatsapp/solicitudes', [SolicitudReservaWhatsappController::class, 'index'])->name('whatsapp.solicitudes');
        Route::post('/whatsapp/solicitudes/{solicitud}/sugerir', [SolicitudReservaWhatsappController::class, 'sugerir'])->name('whatsapp.solicitudes.sugerir');
        Route::post('/whatsapp/solicitudes/{solicitud}/aprobar', [SolicitudReservaWhatsappController::class, 'aprobar'])->name('whatsapp.solicitudes.aprobar');
        Route::post('/whatsapp/solicitudes/{solicitud}/rechazar', [SolicitudReservaWhatsappController::class, 'rechazar'])->name('whatsapp.solicitudes.rechazar');
        Route::post('/whatsapp/solicitudes/{solicitud}/confirmar-prenda', [SolicitudReservaWhatsappController::class, 'confirmarPrenda'])->name('whatsapp.solicitudes.confirmar-prenda');
        Route::delete('/whatsapp/solicitudes/{solicitud}', [SolicitudReservaWhatsappController::class, 'destroy'])->name('whatsapp.solicitudes.destroy');

        Route::post('/whatsapp/chat/{numero}/abrir', [WhatsappChatController::class, 'abrir'])->name('whatsapp.chat.abrir');
        Route::get('/whatsapp/chat/{numero}/mensajes', [WhatsappChatController::class, 'mensajes'])->name('whatsapp.chat.mensajes');
        Route::post('/whatsapp/chat/{numero}/enviar', [WhatsappChatController::class, 'enviar'])->name('whatsapp.chat.enviar');
        Route::post('/whatsapp/chat/{numero}/reanudar', [WhatsappChatController::class, 'reanudar'])->name('whatsapp.chat.reanudar');
    });

    Route::middleware('permiso:flujos')->group(function () {
        Route::get('/flujos', [WhatsappFlujoController::class, 'index'])->name('flujos');
        Route::patch('/flujos/nodos/{nodo}/mover', [WhatsappFlujoController::class, 'moverNodo'])->name('flujos.nodos.mover');
        Route::patch('/flujos/nodos/{nodo}', [WhatsappFlujoController::class, 'actualizarNodo'])->name('flujos.nodos.actualizar');
        Route::post('/flujos/nodos/{nodo}/imagen', [WhatsappFlujoController::class, 'subirImagenNodo'])->name('flujos.nodos.imagen.subir');
        Route::delete('/flujos/nodos/{nodo}/imagen', [WhatsappFlujoController::class, 'eliminarImagenNodo'])->name('flujos.nodos.imagen.eliminar');
        Route::post('/flujos/nodos', [WhatsappFlujoController::class, 'crearNodo'])->name('flujos.nodos.crear');
        Route::delete('/flujos/nodos/{nodo}', [WhatsappFlujoController::class, 'eliminarNodo'])->name('flujos.nodos.eliminar');
        Route::post('/flujos/conexiones', [WhatsappFlujoController::class, 'crearConexion'])->name('flujos.conexiones.crear');
        Route::delete('/flujos/conexiones/{conexion}', [WhatsappFlujoController::class, 'eliminarConexion'])->name('flujos.conexiones.eliminar');
        Route::post('/flujos/restaurar', [WhatsappFlujoController::class, 'restaurar'])->name('flujos.restaurar');
        Route::post('/flujos/probar', [WhatsappController::class, 'probar'])->name('flujos.probar');
        Route::post('/flujos/probar/reiniciar', [WhatsappController::class, 'reiniciarPrueba'])->name('flujos.probar.reiniciar');
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
