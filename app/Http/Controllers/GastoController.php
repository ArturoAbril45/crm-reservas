<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ScopedToSucursal;
use App\Models\CajaMovimiento;
use App\Models\Gasto;
use App\Models\Sucursal;
use Illuminate\Http\Request;

class GastoController extends Controller
{
    use ScopedToSucursal;

    public const CATEGORIAS = ['Servicios', 'Mantenimiento', 'Nómina', 'Insumos', 'Transporte', 'Otro'];
    public const FORMAS_PAGO = ['Efectivo', 'Transferencia'];

    public function index(Request $request)
    {
        $sucursal = $this->sucursalActual($request);

        $gastos = $sucursal
            ? Gasto::with('usuario')
                ->where(fn ($q) => $q->where('sucursal_id', $sucursal->id)->orWhere('es_general', true))
                ->latest('fecha')
                ->limit(20)
                ->get()
            : collect();

        $categorias = self::CATEGORIAS;
        $formasPago = self::FORMAS_PAGO;
        $sucursales = Sucursal::orderBy('nombre')->get();

        return view('gastos.index', compact('sucursal', 'gastos', 'categorias', 'formasPago', 'sucursales'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'sucursal_id'  => ['required', 'exists:sucursales,id'],
            'categoria'    => ['required', 'in:' . implode(',', self::CATEGORIAS)],
            'descripcion'  => ['nullable', 'string', 'max:255'],
            'forma_pago'   => ['required', 'in:' . implode(',', self::FORMAS_PAGO)],
            'monto'        => ['required', 'numeric', 'min:0.01'],
        ]);

        $gasto = Gasto::create([
            'fecha'       => now()->toDateString(),
            'sucursal_id' => $data['sucursal_id'],
            'categoria'   => $data['categoria'],
            'descripcion' => $data['descripcion'] ?? null,
            'forma_pago'  => $data['forma_pago'],
            'monto'       => $data['monto'],
            'usuario_id'  => $request->user()->id,
            'es_general'  => false,
        ]);

        if ($data['forma_pago'] === 'Efectivo') {
            CajaMovimiento::create([
                'fecha'       => now()->toDateString(),
                'sucursal_id' => $data['sucursal_id'],
                'tipo'        => 'EGRESO',
                'concepto'    => 'Gasto: ' . $data['categoria'],
                'monto'       => $data['monto'],
                'usuario_id'  => $request->user()->id,
                'referencia'  => 'GASTO-' . $gasto->id,
            ]);
        }

        return redirect()->route('gastos')->with('status', 'Gasto registrado correctamente.');
    }
}
