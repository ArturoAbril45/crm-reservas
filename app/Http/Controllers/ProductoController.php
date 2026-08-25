<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ScopedToSucursal;
use App\Models\Almacen;
use App\Models\Inventario;
use App\Models\Producto;
use App\Models\ProductoPrecioLocal;
use App\Models\ProductoPresentacionPrecioLocal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ProductoController extends Controller
{
    use ScopedToSucursal;

    public function index(Request $request)
    {
        $sucursal = $this->sucursalActual($request);

        $almacenes = Almacen::with('sucursal')->orderBy('nombre')->get();

        $almacenId = $request->query('almacen', optional(
            $sucursal ? $almacenes->firstWhere('sucursal_id', $sucursal->id) : null
        )->id ?? optional($almacenes->first())->id);

        $almacenActual = $almacenId ? $almacenes->firstWhere('id', (int) $almacenId) : null;

        $productos = Producto::with(['presentaciones', 'inventarios', 'preciosLocales', 'presentacionPreciosLocales'])
            ->orderBy('nombre')
            ->get();

        $tipos = Producto::TIPOS;

        return view('productos.index', compact('sucursal', 'almacenes', 'almacenActual', 'productos', 'tipos'));
    }

    public function store(Request $request)
    {
        $data = $this->validarProducto($request);

        $almacen = Almacen::findOrFail($data['almacen_id']);

        $producto = Producto::create(collect($data)->except(['almacen_id', 'stock'])->all());

        $producto->presentaciones()->create([
            'nombre'  => $data['unidad'],
            'factor'  => 1,
            'costo'   => $data['costo'],
            'precio'  => $data['precio'],
            'activo'  => true,
        ]);

        if ($producto->controla_stock && ! empty($data['stock'])) {
            Inventario::create([
                'producto_id' => $producto->id,
                'almacen_id'  => $almacen->id,
                'stock'       => $data['stock'],
            ]);
        }

        return redirect()->route('productos', ['almacen' => $almacen->id])->with('status', 'Producto agregado correctamente.');
    }

    public function update(Request $request, Producto $producto)
    {
        $data = $this->validarProducto($request, $producto);

        $producto->update(collect($data)->except(['almacen_id', 'stock'])->all());

        return redirect()->route('productos', ['almacen' => $data['almacen_id']])->with('status', 'Producto actualizado correctamente.');
    }

    /**
     * Actualiza, en un solo formulario, el precio general de respaldo y los precios
     * por presentación de un producto en TODOS los almacenes (igual que el sistema
     * de referencia): un input vacío borra el override y vuelve a la jerarquía normal.
     */
    public function preciosLocales(Request $request, Producto $producto)
    {
        $almacenes = Almacen::all();
        $presentaciones = $producto->presentaciones;

        DB::transaction(function () use ($request, $producto, $almacenes, $presentaciones) {
            foreach ($almacenes as $almacen) {
                $general = $request->input("precio_local_{$almacen->id}");

                if ($general === null || trim((string) $general) === '') {
                    ProductoPrecioLocal::where('producto_id', $producto->id)->where('almacen_id', $almacen->id)->delete();
                } else {
                    ProductoPrecioLocal::updateOrCreate(
                        ['producto_id' => $producto->id, 'almacen_id' => $almacen->id],
                        ['precio' => $general]
                    );
                }

                foreach ($presentaciones as $presentacion) {
                    $clave = 'precio_local_' . $almacen->id . '_presentacion_' . bin2hex($presentacion->nombre);
                    $valor = $request->input($clave);

                    if ($valor === null || trim((string) $valor) === '') {
                        ProductoPresentacionPrecioLocal::where('producto_id', $producto->id)
                            ->where('almacen_id', $almacen->id)
                            ->where('presentacion', $presentacion->nombre)
                            ->delete();
                    } else {
                        ProductoPresentacionPrecioLocal::updateOrCreate(
                            ['producto_id' => $producto->id, 'almacen_id' => $almacen->id, 'presentacion' => $presentacion->nombre],
                            ['precio' => $valor]
                        );
                    }
                }
            }
        });

        return back()->with('status', 'Precios por local actualizados correctamente.');
    }

    private function validarProducto(Request $request, ?Producto $producto = null): array
    {
        $data = $request->validate([
            'almacen_id'                => ['required', 'exists:almacenes,id'],
            'codigo'                    => ['required', 'string', 'max:100', Rule::unique('productos', 'codigo')->ignore($producto?->id)],
            'nombre'                    => ['required', 'string', 'max:255'],
            'unidad'                    => ['required', 'string', 'max:100'],
            'tipo_item'                 => ['required', 'in:' . implode(',', Producto::TIPOS)],
            'costo'                     => ['required', 'numeric', 'min:0'],
            'precio'                    => ['required', 'numeric', 'min:0'],
            'stock'                     => ['nullable', 'integer', 'min:0'],
            'stock_minimo'              => ['nullable', 'numeric', 'min:0'],
            'stock_minimo_presentacion' => ['nullable', 'string', 'max:100'],
        ]);

        $data['controla_stock'] = $data['tipo_item'] !== 'Servicio';

        if (! $data['controla_stock']) {
            $data['stock_minimo'] = 0;
            $data['stock_minimo_presentacion'] = null;
        } elseif ($producto) {
            // La presentación de alerta solo puede ser una presentación activa de ESTE producto.
            if (! empty($data['stock_minimo_presentacion'])) {
                $valida = $producto->presentaciones()
                    ->where('activo', true)
                    ->where('nombre', $data['stock_minimo_presentacion'])
                    ->exists();

                if (! $valida) {
                    $data['stock_minimo_presentacion'] = null;
                }
            }
        }

        return $data;
    }
}
