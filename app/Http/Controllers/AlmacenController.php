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
}
