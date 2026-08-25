<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ScopedToSucursal;
use App\Models\Almacen;
use App\Models\Inventario;
use App\Models\Producto;
use App\Models\ProductoPresentacion;
use App\Models\Sucursal;
use App\Models\Traspaso;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TraspasoController extends Controller
{
    use ScopedToSucursal;

    public function index(Request $request)
    {
        $sucursales = Sucursal::withCount('almacenes')->orderBy('nombre')->get();
        $sucursal = $this->sucursalActual($request);
        $sucursal = $sucursal ? $sucursales->firstWhere('id', $sucursal->id) : $sucursales->first();
        $otras = $sucursales->reject(fn ($s) => $sucursal && $s->id === $sucursal->id);

        $almacenes = Almacen::orderBy('nombre')->get()->keyBy('sucursal_id');

        $productos = Producto::with(['presentaciones' => fn ($q) => $q->where('activo', true), 'inventarios'])
            ->orderBy('nombre')
            ->get();

        $productosPorSucursal = $otras->mapWithKeys(function ($otra) use ($productos, $almacenes) {
            $almacen = $almacenes->get($otra->id);

            $lista = $almacen
                ? $productos->filter(fn ($p) => $p->stockEnAlmacen($almacen->id) > 0)
                    ->map(fn ($p) => [
                        'id'             => $p->id,
                        'nombre'         => $p->nombre,
                        'unidad'         => $p->unidad,
                        'stock'          => $p->stockEnAlmacen($almacen->id),
                        'presentaciones' => $p->presentaciones->values(),
                    ])->values()
                : collect();

            return [$otra->id => $lista];
        });

        $traspasos = $sucursal
            ? Traspaso::with(['producto', 'sucursalOrigen', 'sucursalDestino'])
                ->where('sucursal_destino_id', $sucursal->id)
                ->latest()
                ->limit(15)
                ->get()
                ->groupBy(fn ($t) => $t->created_at->timestamp . '-' . $t->sucursal_origen_id)
            : collect();

        return view('traspasos.index', compact('sucursal', 'otras', 'productosPorSucursal', 'traspasos'));
    }

    public function store(Request $request)
    {
        $sucursalDestino = $this->sucursalActual($request);

        if (! $sucursalDestino) {
            return back()->withErrors(['producto_id' => 'Primero seleccioná tu sucursal.']);
        }

        $data = $request->validate([
            'sucursal_origen_id'         => ['required', 'exists:sucursales,id'],
            'producto_id'                => ['required', 'array', 'min:1'],
            'producto_id.*'              => ['required', 'exists:productos,id'],
            'presentacion_id'            => ['array'],
            'presentacion_id.*'          => ['nullable', 'exists:producto_presentaciones,id'],
            'cantidad_presentacion'      => ['required', 'array'],
            'cantidad_presentacion.*'    => ['required', 'numeric', 'min:0.0001'],
        ]);

        if ((int) $data['sucursal_origen_id'] === $sucursalDestino->id) {
            return back()->withErrors(['sucursal_origen_id' => 'Elegí una sucursal distinta a la tuya.']);
        }

        $almacenOrigen = Almacen::where('sucursal_id', $data['sucursal_origen_id'])->orderBy('nombre')->first();
        $almacenDestino = Almacen::where('sucursal_id', $sucursalDestino->id)->orderBy('nombre')->first();

        if (! $almacenOrigen) {
            return back()->withErrors(['producto_id' => 'La sucursal de origen todavía no tiene un almacén creado.']);
        }

        if (! $almacenDestino) {
            return back()->withErrors(['producto_id' => 'Tu sucursal todavía no tiene un almacén creado.']);
        }

        // Se puede combinar más de una presentación del MISMO producto en una sola línea
        // (ej. 2 jabas de 12 + 5 unidades = 29 unidades), igual que en el sistema de referencia.
        $lineas = [];
        $stockConsumido = [];

        foreach ($data['producto_id'] as $i => $productoId) {
            $producto = Producto::with('inventarios')->find($productoId);

            if (! $producto) {
                return back()->withErrors(['producto_id' => 'Uno de los productos no existe.']);
            }

            $presentacionId = $data['presentacion_id'][$i] ?? null;
            $factor = 1.0;

            if ($presentacionId) {
                $presentacion = ProductoPresentacion::where('producto_id', $producto->id)->find($presentacionId);
                if ($presentacion) {
                    $factor = (float) $presentacion->factor;
                }
            }

            $cantidad = (int) round($data['cantidad_presentacion'][$i] * $factor);

            if ($cantidad < 1) {
                continue;
            }

            $stockOrigen = $producto->stockEnAlmacen($almacenOrigen->id);
            $yaConsumido = $stockConsumido[$producto->id] ?? 0;

            if ($yaConsumido + $cantidad > $stockOrigen) {
                return back()->withErrors(['producto_id' => "No hay suficiente stock de {$producto->nombre} en la sucursal de origen. Disponible: {$stockOrigen} unidades, solicitado: " . ($yaConsumido + $cantidad) . '.']);
            }

            $stockConsumido[$producto->id] = $yaConsumido + $cantidad;

            $lineas[] = ['producto' => $producto, 'cantidad' => $cantidad];
        }

        if (empty($lineas)) {
            return back()->withErrors(['producto_id' => 'Agregá al menos un producto con cantidad mayor a cero.']);
        }

        DB::transaction(function () use ($lineas, $data, $sucursalDestino, $almacenOrigen, $almacenDestino) {
            foreach ($lineas as $linea) {
                $producto = $linea['producto'];
                $cantidad = $linea['cantidad'];

                Inventario::where('producto_id', $producto->id)->where('almacen_id', $almacenOrigen->id)->decrement('stock', $cantidad);

                Inventario::updateOrCreate(
                    ['producto_id' => $producto->id, 'almacen_id' => $almacenDestino->id],
                    []
                )->increment('stock', $cantidad);

                Traspaso::create([
                    'sucursal_origen_id'  => $data['sucursal_origen_id'],
                    'sucursal_destino_id' => $sucursalDestino->id,
                    'producto_id'         => $producto->id,
                    'cantidad'            => $cantidad,
                ]);
            }
        });

        return redirect()->route('traspasos')->with('status', 'Traspaso registrado correctamente.');
    }
}
