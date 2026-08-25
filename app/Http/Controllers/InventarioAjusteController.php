<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ScopedToSucursal;
use App\Models\Almacen;
use App\Models\Inventario;
use App\Models\InventarioAjuste;
use App\Models\Producto;
use App\Models\ProductoPresentacion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InventarioAjusteController extends Controller
{
    use ScopedToSucursal;

    public function create(Request $request)
    {
        $sucursal = $this->sucursalActual($request);

        $almacenes = Almacen::with('sucursal')->orderBy('nombre')->get();

        $almacenId = $request->query('almacen', optional(
            $sucursal ? $almacenes->firstWhere('sucursal_id', $sucursal->id) : null
        )->id ?? optional($almacenes->first())->id);

        $almacenActual = $almacenId ? $almacenes->firstWhere('id', (int) $almacenId) : null;

        $productos = Producto::where('controla_stock', true)
            ->with(['presentaciones' => fn ($q) => $q->where('activo', true)])
            ->orderBy('nombre')
            ->get();

        $historial = InventarioAjuste::with(['producto', 'almacen.sucursal', 'usuario'])
            ->latest()
            ->limit(100)
            ->get();

        return view('productos.carga-inicial', compact('almacenes', 'almacenActual', 'productos', 'historial'));
    }

    /**
     * Carga/ajusta el stock de productos ya creados en un almacén sin pasar por
     * Compras: no genera compra ni movimiento de caja, solo fija (no suma) el
     * stock nuevo y deja un registro del ajuste — igual que "Saldo inicial" en
     * el sistema de referencia.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'almacen_id'         => ['required', 'exists:almacenes,id'],
            'fecha'              => ['required', 'date'],
            'observacion'        => ['nullable', 'string', 'max:300'],
            'aplicar_producto'   => ['required', 'array', 'min:1'],
            'aplicar_producto.*' => ['required', 'exists:productos,id'],
            'producto_id'        => ['array'],
            'producto_id.*'      => ['required', 'exists:productos,id'],
            'presentacion_id'    => ['array'],
            'presentacion_id.*'  => ['nullable', 'exists:producto_presentaciones,id'],
            'cantidad'           => ['array'],
            'cantidad.*'         => ['required', 'numeric', 'min:0'],
        ]);

        $almacen = Almacen::findOrFail($data['almacen_id']);
        $aplicar = array_map('intval', $data['aplicar_producto']);

        // Agrupamos por producto: el stock nuevo es la SUMA de todas las líneas
        // de ese producto (ej. "2 jabas + 5 unidades" = un solo stock nuevo).
        // Solo se procesan los productos marcados con "Aplicar".
        $porProducto = [];

        foreach ($data['producto_id'] ?? [] as $i => $productoId) {
            if (! in_array((int) $productoId, $aplicar, true)) {
                continue;
            }

            $producto = Producto::find($productoId);

            if (! $producto || ! $producto->controla_stock) {
                continue;
            }

            $presentacionId = $data['presentacion_id'][$i] ?? null;
            $factor = 1.0;
            $nombrePresentacion = $producto->unidad;

            if ($presentacionId) {
                $presentacion = ProductoPresentacion::where('producto_id', $producto->id)->find($presentacionId);
                if ($presentacion) {
                    $factor = (float) $presentacion->factor;
                    $nombrePresentacion = $presentacion->nombre;
                }
            }

            $cantidad = (float) ($data['cantidad'][$i] ?? 0);

            if ($cantidad <= 0) {
                continue;
            }

            $cantidadBase = (int) round($cantidad * $factor);

            $porProducto[$productoId]['producto'] ??= $producto;
            $porProducto[$productoId]['detalle'][] = rtrim(rtrim((string) $cantidad, '0'), '.') . ' ' . $nombrePresentacion;
            $porProducto[$productoId]['stockNuevo'] = ($porProducto[$productoId]['stockNuevo'] ?? 0) + $cantidadBase;
        }

        // Los productos marcados con "Aplicar" pero sin ninguna cantidad cargada
        // también cuentan: su saldo físico pasa a ser 0 (mismo criterio que el
        // sistema de referencia, donde el total físico arranca en 0).
        foreach ($aplicar as $productoId) {
            if (! isset($porProducto[$productoId])) {
                $producto = Producto::find($productoId);
                if ($producto && $producto->controla_stock) {
                    $porProducto[$productoId] = ['producto' => $producto, 'detalle' => [], 'stockNuevo' => 0];
                }
            }
        }

        if (empty($porProducto)) {
            return back()->withErrors(['aplicar_producto' => 'Marcá al menos un producto para aplicar el saldo inicial.']);
        }

        DB::transaction(function () use ($request, $porProducto, $almacen, $data) {
            foreach ($porProducto as $grupo) {
                $producto = $grupo['producto'];
                $stockNuevo = $grupo['stockNuevo'];

                $inventario = Inventario::firstOrNew(['producto_id' => $producto->id, 'almacen_id' => $almacen->id]);
                $stockAnterior = (int) ($inventario->stock ?? 0);
                $inventario->stock = $stockNuevo;
                $inventario->save();

                InventarioAjuste::create([
                    'fecha'          => $data['fecha'],
                    'almacen_id'     => $almacen->id,
                    'producto_id'    => $producto->id,
                    'tipo'           => 'SALDO_INICIAL',
                    'stock_anterior' => $stockAnterior,
                    'stock_nuevo'    => $stockNuevo,
                    'diferencia'     => $stockNuevo - $stockAnterior,
                    'detalle'        => collect([implode(' + ', $grupo['detalle']) ?: '0 unidades', $data['observacion'] ?? null])->filter()->implode(' · '),
                    'usuario_id'     => $request->user()->id,
                ]);
            }
        });

        return redirect()->route('productos.carga-inicial', ['almacen' => $almacen->id])
            ->with('status', 'Saldo inicial guardado para ' . $almacen->nombre . '. No se generó compra ni movimiento de caja.');
    }
}
