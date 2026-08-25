<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ScopedToSucursal;
use App\Models\CajaMovimiento;
use Illuminate\Http\Request;

class CajaController extends Controller
{
    use ScopedToSucursal;

    public function index(Request $request)
    {
        $sucursal = $this->sucursalActual($request);
        $fecha = $request->query('fecha', now()->toDateString());

        $movimientos = $sucursal
            ? CajaMovimiento::with('usuario')
                ->where('sucursal_id', $sucursal->id)
                ->whereDate('fecha', $fecha)
                ->latest()
                ->get()
            : collect();

        $total = $movimientos->sum(fn ($m) => match ($m->tipo) {
            'INGRESO' => (float) $m->monto,
            'EGRESO' => -1 * (float) $m->monto,
            default => 0,
        });

        return view('caja.index', compact('sucursal', 'movimientos', 'fecha', 'total'));
    }

    public function store(Request $request)
    {
        $sucursal = $this->sucursalActual($request);

        if (! $sucursal) {
            return back()->withErrors(['tipo' => 'Primero seleccioná una sucursal.']);
        }

        $data = $request->validate([
            'tipo'     => ['required', 'in:' . implode(',', CajaMovimiento::TIPOS)],
            'concepto' => ['required', 'string', 'max:255'],
            'monto'    => ['required', 'numeric', 'min:0.01'],
        ]);

        CajaMovimiento::create($data + [
            'fecha'       => now()->toDateString(),
            'sucursal_id' => $sucursal->id,
            'usuario_id'  => $request->user()->id,
        ]);

        return redirect()->route('caja')->with('status', 'Movimiento registrado correctamente.');
    }
}
