<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use App\Models\ProductoPresentacion;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProductoPresentacionController extends Controller
{
    public function store(Request $request, Producto $producto)
    {
        $data = $request->validate([
            'nombre' => [
                'required', 'string', 'max:100',
                Rule::unique('producto_presentaciones')->where('producto_id', $producto->id),
            ],
            'factor' => ['required', 'numeric', 'min:0.0001'],
            'costo'  => ['nullable', 'numeric', 'min:0'],
            'precio' => ['nullable', 'numeric', 'min:0'],
        ]);

        $producto->presentaciones()->create($data + ['activo' => true]);

        return redirect()->route('productos')->with('status', 'Presentación agregada correctamente.');
    }

    public function update(Request $request, Producto $producto, ProductoPresentacion $presentacion)
    {
        $data = $request->validate([
            'factor' => ['required', 'numeric', 'min:0.0001'],
            'costo'  => ['nullable', 'numeric', 'min:0'],
            'precio' => ['nullable', 'numeric', 'min:0'],
        ]);

        $presentacion->update($data);

        return redirect()->route('productos')->with('status', 'Presentación actualizada correctamente.');
    }

    public function activar(Producto $producto, ProductoPresentacion $presentacion)
    {
        $activando = ! $presentacion->activo;

        if (! $activando) {
            $quedanActivas = $producto->presentaciones()
                ->where('activo', true)
                ->where('id', '!=', $presentacion->id)
                ->exists();

            if (! $quedanActivas) {
                return back()->withErrors(['presentacion' => 'El producto debe tener al menos una presentación habilitada.']);
            }
        }

        $presentacion->update(['activo' => $activando]);

        // Si se desactivó justo la presentación usada para la alerta de stock mínimo,
        // reasignamos automáticamente a otra presentación activa (o la dejamos sin definir).
        if (! $activando && $producto->stock_minimo_presentacion === $presentacion->nombre) {
            $otra = $producto->presentaciones()->where('activo', true)->first();
            $producto->update(['stock_minimo_presentacion' => $otra?->nombre]);
        }

        return redirect()->route('productos')->with('status', 'Presentación actualizada correctamente.');
    }
}
