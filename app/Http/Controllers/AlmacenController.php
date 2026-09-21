<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ScopedToSucursal;
use App\Models\Almacen;
use Illuminate\Http\Request;

class AlmacenController extends Controller
{
    use ScopedToSucursal;

    public function index(Request $request)
    {
        $sucursal = $this->sucursalActual($request);

        $almacenes = $sucursal
            ? Almacen::withCount('inventarios')->where('sucursal_id', $sucursal->id)->orderBy('nombre')->get()
            : collect();

        return view('almacenes.index', compact('almacenes', 'sucursal'));
    }

    public function store(Request $request)
    {
        $sucursal = $this->sucursalActual($request);

        if (! $sucursal) {
            return back()->withErrors(['nombre' => 'Primero seleccioná una sucursal.']);
        }

        $data = $request->validate([
            'nombre'    => ['required', 'string', 'max:255'],
            'ubicacion' => ['nullable', 'string', 'max:255'],
        ]);

        Almacen::create($data + ['sucursal_id' => $sucursal->id]);

        return redirect()->route('almacenes')->with('status', 'Almacén creado correctamente.');
    }

    public function update(Request $request, Almacen $almacen)
    {
        $data = $request->validate([
            'nombre'    => ['required', 'string', 'max:255'],
            'ubicacion' => ['nullable', 'string', 'max:255'],
        ]);

        $almacen->update($data);

        return redirect()->route('almacenes')->with('status', 'Almacén actualizado correctamente.');
    }

    public function destroy(Almacen $almacen)
    {
        if ($almacen->compras()->exists()) {
            return back()->withErrors(['nombre' => 'Este almacén tiene compras registradas, no se puede eliminar.']);
        }

        // Borrar el almacén también borra en cascada su inventario y sus precios
        // locales (stock y precios propios de este almacén), no el catálogo global.
        $almacen->delete();

        return redirect()->route('almacenes')->with('status', 'Almacén eliminado correctamente.');
    }
}
