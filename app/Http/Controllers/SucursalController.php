<?php

namespace App\Http\Controllers;

use App\Models\Sucursal;
use Illuminate\Http\Request;

class SucursalController extends Controller
{
    public function index()
    {
        $sucursales = Sucursal::orderBy('nombre')->get();

        return view('sucursales.index', compact('sucursales'));
    }

    public function seleccionar()
    {
        $sucursales = Sucursal::where('activa', true)->orderBy('nombre')->get();

        return view('sucursales.seleccionar', compact('sucursales'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nombre'    => ['required', 'string', 'max:255'],
            'direccion' => ['nullable', 'string', 'max:255'],
            'telefono'  => ['nullable', 'string', 'max:50'],
        ]);

        $sucursal = Sucursal::create($data + ['activa' => true]);

        session(['sucursal_actual_id' => $sucursal->id]);

        return redirect()->route('sucursales.index')->with('status', 'Sucursal creada correctamente.');
    }

    public function cambiar(Sucursal $sucursal)
    {
        session(['sucursal_actual_id' => $sucursal->id]);
        session()->flash('sucursal_entrando', $sucursal->nombre);

        return redirect()->route('reservas');
    }

    public function update(Request $request, Sucursal $sucursal)
    {
        $data = $request->validate([
            'nombre'    => ['required', 'string', 'max:255'],
            'direccion' => ['nullable', 'string', 'max:255'],
            'telefono'  => ['nullable', 'string', 'max:50'],
            'activa'    => ['nullable', 'boolean'],
        ]);

        $data['activa'] = $request->boolean('activa');

        $sucursal->update($data);

        return redirect()->route('sucursales.index')->with('status', 'Sucursal actualizada correctamente.');
    }
}
