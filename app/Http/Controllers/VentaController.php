<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ScopedToSucursal;
use App\Models\Almacen;
use App\Models\CajaMovimiento;
use App\Models\Inventario;
use App\Models\Producto;
use App\Models\ProductoPresentacion;
use App\Models\Sucursal;
use App\Models\Venta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VentaController extends Controller
{
    use ScopedToSucursal;

    public function index(Request $request)
    {
        $sucursal = $this->sucursalActual($request);

        $sucursales = Sucursal::orderBy('nombre')->get();
        $almacenes = Almacen::orderBy('nombre')->get()->keyBy('sucursal_id');

        $productos = Producto::with(['presentaciones' => fn ($q) => $q->where('activo', true), 'inventarios', 'preciosLocales', 'presentacionPreciosLocales'])
            ->orderBy('nombre')
            ->get();

        $productosPorSucursal = $sucursales->mapWithKeys(function ($s) use ($productos, $almacenes) {
            $almacen = $almacenes->get($s->id);

            $lista = $almacen
                ? $productos->map(fn ($p) => [
                    'id'             => $p->id,
                    'nombre'         => $p->nombre,
                    'unidad'         => $p->unidad,
                    'controla_stock' => $p->controla_stock,
                    'stock'          => $p->stockEnAlmacen($almacen->id),
                    'precio'         => $p->precioEnAlmacen($almacen->id),
                    'presentaciones' => $p->presentaciones->map(fn ($pr) => [
                        'id'     => $pr->id,
                        'nombre' => $pr->nombre,
                        'factor' => $pr->factor,
                        'precio' => $p->precioEnAlmacen($almacen->id, $pr->nombre),
                    ])->values(),
                ])->values()
                : collect();

            return [$s->id => $lista];
        });

        $ventas = $sucursal
            ? Venta::with(['detalles.producto'])->where('sucursal_id', $sucursal->id)->latest()->limit(20)->get()
            : collect();

        $jornada = $this->opcionesJornada();

        return view('ventas.index', compact('sucursal', 'sucursales', 'productosPorSucursal', 'ventas', 'jornada'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'sucursal_id'              => ['required', 'exists:sucursales,id'],
            'fecha_jornada'            => ['required', 'date'],
            'tipo_pago'                => ['required', 'in:efectivo,transferencia'],
            'captura_pago'             => ['nullable', 'required_if:tipo_pago,transferencia', 'image', 'max:4096'],
            'observaciones'            => ['nullable', 'string', 'max:500'],
            'producto_id'              => ['required', 'array', 'min:1'],
            'producto_id.*'            => ['required', 'exists:productos,id'],
            'presentacion_id'          => ['array'],
            'presentacion_id.*'        => ['nullable', 'exists:producto_presentaciones,id'],
            'cantidad_presentacion'    => ['required', 'array'],
            'cantidad_presentacion.*'  => ['required', 'numeric', 'min:0.0001'],
        ]);

        $fechaJornada = $this->jornadaValida($data['fecha_jornada']);

        if (! $fechaJornada) {
            return back()->withErrors(['fecha_jornada' => 'Fecha de jornada inválida. Solo se permite hoy y, hasta las 2:00 a. m., el día anterior.']);
        }

        $almacen = Almacen::where('sucursal_id', $data['sucursal_id'])->orderBy('nombre')->first();

        if (! $almacen) {
            return back()->withErrors(['producto_id' => 'Esa sucursal todavía no tiene un almacén creado.']);
        }

        // Recalculamos todo en el servidor: nunca confiamos en precios/factores enviados por el cliente.
        $lineas = [];
        $stockConsumido = [];

        foreach ($data['producto_id'] as $i => $productoId) {
            $producto = Producto::with(['presentaciones', 'inventarios', 'preciosLocales', 'presentacionPreciosLocales'])->find($productoId);

            if (! $producto) {
                return back()->withErrors(['producto_id' => 'Uno de los productos no existe.']);
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

            $precioUnitario = $producto->precioEnAlmacen($almacen->id, $nombrePresentacion);

            $cantidadPresentacion = (float) $data['cantidad_presentacion'][$i];
            $cantidadBase = (int) round($cantidadPresentacion * $factor);

            if ($producto->controla_stock) {
                $stockDisponible = $producto->stockEnAlmacen($almacen->id);
                $yaConsumido = $stockConsumido[$producto->id] ?? 0;
                if ($yaConsumido + $cantidadBase > $stockDisponible) {
                    return back()->withErrors(['producto_id' => "No hay suficiente stock de {$producto->nombre}. Disponible: {$stockDisponible} unidades."]);
                }
                $stockConsumido[$producto->id] = $yaConsumido + $cantidadBase;
            }

            $lineas[] = [
                'producto'              => $producto,
                'presentacion'          => $nombrePresentacion,
                'factor_presentacion'   => $factor,
                'cantidad_presentacion' => $cantidadPresentacion,
                'cantidad'              => $cantidadBase,
                'precio_unitario'       => $precioUnitario,
                'costo_unitario'        => $factor > 0 ? round(((float) $producto->costo) / max($factor, 1), 6) : 0,
                'total'                 => round($precioUnitario * $cantidadPresentacion, 2),
            ];
        }

        if (empty($lineas)) {
            return back()->withErrors(['producto_id' => 'Agregá al menos un producto.']);
        }

        if ($request->hasFile('captura_pago')) {
            $data['captura_pago'] = $request->file('captura_pago')->store('ventas', 'public');
        }

        $totalGeneral = round(collect($lineas)->sum('total'), 2);

        $venta = DB::transaction(function () use ($data, $fechaJornada, $lineas, $totalGeneral, $request, $almacen) {
            $venta = Venta::create([
                'numero'        => 'PENDIENTE',
                'sucursal_id'   => $data['sucursal_id'],
                'producto_id'   => $lineas[0]['producto']->id,
                'fecha'         => $fechaJornada,
                'tipo_pago'     => $data['tipo_pago'],
                'captura_pago'  => $data['captura_pago'] ?? null,
                'observaciones' => $data['observaciones'] ?? null,
                'valor_total'   => $totalGeneral,
            ]);

            $venta->update(['numero' => 'NV-' . str_pad((string) $venta->id, 6, '0', STR_PAD_LEFT)]);

            foreach ($lineas as $linea) {
                $venta->detalles()->create([
                    'producto_id'           => $linea['producto']->id,
                    'presentacion'          => $linea['presentacion'],
                    'factor_presentacion'   => $linea['factor_presentacion'],
                    'cantidad_presentacion' => $linea['cantidad_presentacion'],
                    'cantidad'              => $linea['cantidad'],
                    'precio_unitario'       => $linea['precio_unitario'],
                    'costo_unitario'        => $linea['costo_unitario'],
                    'total'                 => $linea['total'],
                ]);

                if ($linea['producto']->controla_stock) {
                    Inventario::where('producto_id', $linea['producto']->id)
                        ->where('almacen_id', $almacen->id)
                        ->decrement('stock', $linea['cantidad']);
                }
            }

            CajaMovimiento::create([
                'fecha'       => now()->toDateString(),
                'sucursal_id' => $data['sucursal_id'],
                'tipo'        => 'INGRESO',
                'concepto'    => 'Venta ' . $venta->numero,
                'monto'       => $totalGeneral,
                'usuario_id'  => $request->user()->id,
                'referencia'  => 'VENTA-' . $venta->id,
            ]);

            return $venta;
        });

        return redirect()->route('ventas')->with('status', 'Venta ' . $venta->numero . ' registrada correctamente.');
    }

    /**
     * Igual que en el sistema de referencia: hasta las 2:00 a. m. se puede elegir
     * "ayer" u "hoy" para la jornada de la venta; después de esa hora, solo "hoy".
     */
    private function opcionesJornada(): array
    {
        $ahora = now();
        $hoy = $ahora->toDateString();
        $ayer = $ahora->copy()->subDay()->toDateString();

        $ayerHabilitado = $ahora->hour < 2 || ($ahora->hour === 2 && $ahora->minute === 0 && $ahora->second === 0);

        $opciones = [$hoy => "Hoy — {$hoy}"];
        if ($ayerHabilitado) {
            $opciones = [$ayer => "Ayer — {$ayer}"] + $opciones;
        }

        return [
            'opciones'       => $opciones,
            'permitidas'     => array_keys($opciones),
            'ayer_habilitado' => $ayerHabilitado,
            'hoy'            => $hoy,
        ];
    }

    private function jornadaValida(?string $valor): ?string
    {
        $jornada = $this->opcionesJornada();

        return in_array($valor, $jornada['permitidas'], true) ? $valor : null;
    }
}
