<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ScopedToSucursal;
use App\Models\Almacen;
use App\Models\Compra;
use App\Models\Inventario;
use App\Models\Producto;
use App\Models\ProductoPresentacion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CompraController extends Controller
{
    use ScopedToSucursal;

    public const FORMAS_PAGO = ['Efectivo', 'Transferencia', 'Crédito'];

    public function index(Request $request)
    {
        $sucursal = $this->sucursalActual($request);

        $almacenes = $sucursal
            ? Almacen::where('sucursal_id', $sucursal->id)->orderBy('nombre')->get()
            : collect();

        // El catálogo de productos es global: cualquier producto puede comprarse
        // hacia cualquier almacén (mismo criterio que el sistema de referencia).
        $productos = Producto::with(['presentaciones' => fn ($q) => $q->where('activo', true)])
            ->orderBy('nombre')
            ->get(['id', 'nombre']);

        $productosPorAlmacen = $almacenes->mapWithKeys(fn ($almacen) => [$almacen->id => $productos]);

        $compras = $sucursal
            ? Compra::with(['almacen', 'usuario'])
                ->whereIn('almacen_id', $almacenes->pluck('id'))
                ->latest()
                ->limit(20)
                ->get()
            : collect();

        return view('compras.index', compact('sucursal', 'almacenes', 'productosPorAlmacen', 'compras'));
    }

    public function store(Request $request)
    {
        $sucursal = $this->sucursalActual($request);

        if (! $sucursal) {
            return back()->withErrors(['almacen_id' => 'Primero seleccioná una sucursal.']);
        }

        $data = $request->validate([
            'almacen_id'                 => ['required', 'exists:almacenes,id'],
            'producto_id'                => ['required', 'array', 'min:1'],
            'producto_id.*'              => ['required', 'exists:productos,id'],
            'presentacion_id'            => ['array'],
            'presentacion_id.*'          => ['nullable', 'exists:producto_presentaciones,id'],
            'cantidad_presentacion'      => ['required', 'array'],
            'cantidad_presentacion.*'    => ['required', 'numeric', 'min:0.0001'],
            'costo_presentacion'         => ['required', 'array'],
            'costo_presentacion.*'       => ['required', 'numeric', 'min:0'],
            'proveedor'                  => ['nullable', 'string', 'max:255'],
            'forma_pago'                 => ['required', 'in:' . implode(',', self::FORMAS_PAGO)],
            'comprobante_pago'           => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:4096'],
            'observaciones'              => ['nullable', 'string'],
        ]);

        $almacen = Almacen::where('sucursal_id', $sucursal->id)->findOrFail($data['almacen_id']);

        // Se puede comprar más de una presentación del MISMO producto en una sola compra
        // (ej. 3 jabas de 12 + 5 unidades), igual que en Ventas y Traspasos.
        $lineas = [];

        foreach ($data['producto_id'] as $i => $productoId) {
            $producto = Producto::find($productoId);

            if (! $producto) {
                return back()->withErrors(['producto_id' => 'Uno de los productos no existe.']);
            }

            $presentacionId = $data['presentacion_id'][$i] ?? null;
            $factor = 1.0;
            $nombrePresentacion = $producto->unidad;
            $presentacion = null;

            if ($presentacionId) {
                $presentacion = ProductoPresentacion::where('producto_id', $producto->id)->find($presentacionId);
                if ($presentacion) {
                    $factor = (float) $presentacion->factor;
                    $nombrePresentacion = $presentacion->nombre;
                }
            }

            $cantidadPresentacion = (float) $data['cantidad_presentacion'][$i];
            $costoPresentacion = (float) $data['costo_presentacion'][$i];
            $cantidadBase = (int) round($cantidadPresentacion * $factor);

            if ($cantidadBase < 1) {
                continue;
            }

            $lineas[] = [
                'producto'              => $producto,
                'presentacion'          => $presentacion,
                'nombrePresentacion'    => $nombrePresentacion,
                'factor'                => $factor,
                'cantidadPresentacion'  => $cantidadPresentacion,
                'cantidadBase'          => $cantidadBase,
                'costoPresentacion'     => $costoPresentacion,
                'costoUnitario'         => $costoPresentacion / $factor,
                'total'                 => $costoPresentacion * $cantidadPresentacion,
            ];
        }

        if (empty($lineas)) {
            return back()->withErrors(['producto_id' => 'Agregá al menos un producto con cantidad mayor a cero.']);
        }

        $estadoPago = $data['forma_pago'] === 'Crédito' ? 'Pendiente' : 'Cancelado';

        if ($data['forma_pago'] === 'Transferencia' && ! $request->hasFile('comprobante_pago')) {
            return back()->withErrors(['comprobante_pago' => 'Debe adjuntar la foto o PDF del comprobante de pago.']);
        }

        $comprobantePath = $request->hasFile('comprobante_pago')
            ? $request->file('comprobante_pago')->store('comprobantes-compras', 'public')
            : null;

        $totalGeneral = round(collect($lineas)->sum('total'), 2);

        DB::transaction(function () use ($request, $data, $almacen, $lineas, $totalGeneral, $estadoPago, $comprobantePath) {
            $compra = Compra::create([
                'numero'           => 'PENDIENTE',
                'fecha'            => now()->toDateString(),
                'proveedor'        => $data['proveedor'] ?? null,
                'almacen_id'       => $almacen->id,
                'usuario_id'       => $request->user()->id,
                'forma_pago'       => $data['forma_pago'],
                'estado_pago'      => $estadoPago,
                'comprobante_pago' => $comprobantePath,
                'pagado_en'        => $estadoPago === 'Cancelado' ? now() : null,
                'total'            => $totalGeneral,
                'observaciones'    => $data['observaciones'] ?? null,
            ]);

            $compra->update(['numero' => 'CP-' . str_pad((string) $compra->id, 6, '0', STR_PAD_LEFT)]);

            foreach ($lineas as $linea) {
                $compra->detalles()->create([
                    'producto_id'           => $linea['producto']->id,
                    'presentacion'          => $linea['nombrePresentacion'],
                    'factor_presentacion'   => $linea['factor'],
                    'cantidad_presentacion' => $linea['cantidadPresentacion'],
                    'cantidad'              => $linea['cantidadBase'],
                    'costo_presentacion'    => $linea['costoPresentacion'],
                    'costo_unitario'        => $linea['costoUnitario'],
                    'total'                 => $linea['total'],
                ]);

                Inventario::updateOrCreate(
                    ['producto_id' => $linea['producto']->id, 'almacen_id' => $almacen->id],
                    []
                )->increment('stock', $linea['cantidadBase']);

                $linea['producto']->update(['costo' => round($linea['costoUnitario'], 2)]);

                if ($linea['presentacion']) {
                    $linea['presentacion']->update(['costo' => $linea['costoPresentacion']]);
                }
            }
        });

        return redirect()->route('compras')->with('status', 'Compra registrada correctamente.');
    }

    public function marcarPagada(Request $request, Compra $compra)
    {
        if ($compra->estado_pago !== 'Pendiente') {
            return back()->withErrors(['comprobante_pago' => 'Esta compra ya está pagada.']);
        }

        $data = $request->validate([
            'comprobante_pago' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:4096'],
        ]);

        $compra->update([
            'estado_pago'      => 'Cancelado',
            'comprobante_pago' => $request->file('comprobante_pago')->store('comprobantes-compras', 'public'),
            'pagado_en'        => now(),
        ]);

        return redirect()->route('compras')->with('status', 'Compra ' . $compra->numero . ' marcada como pagada.');
    }
}
