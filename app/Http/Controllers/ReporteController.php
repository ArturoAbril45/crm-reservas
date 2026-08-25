<?php

namespace App\Http\Controllers;

use App\Models\Compra;
use App\Models\Gasto;
use App\Models\Sucursal;
use App\Models\Venta;
use Illuminate\Http\Request;

class ReporteController extends Controller
{
    public function index(Request $request)
    {
        $desde = $request->query('desde', now()->subDays(30)->toDateString());
        $hasta = $request->query('hasta', now()->toDateString());
        $sucursalId = $request->query('sucursal_id');

        $sucursales = Sucursal::orderBy('nombre')->get();

        $ventasQuery = Venta::whereDate('created_at', '>=', $desde)->whereDate('created_at', '<=', $hasta);
        $gastosQuery = Gasto::whereDate('fecha', '>=', $desde)->whereDate('fecha', '<=', $hasta);
        $comprasQuery = Compra::whereDate('fecha', '>=', $desde)->whereDate('fecha', '<=', $hasta);

        if ($sucursalId) {
            $ventasQuery->where('sucursal_id', $sucursalId);
            $gastosQuery->where(fn ($q) => $q->where('sucursal_id', $sucursalId)->orWhere('es_general', true));
            $comprasQuery->whereHas('almacen', fn ($q) => $q->where('sucursal_id', $sucursalId));
        }

        $totalVentas = (clone $ventasQuery)->sum('valor_total');
        $notasVentas = (clone $ventasQuery)->count();

        $totalGastos = (clone $gastosQuery)->sum('monto');
        $totalCompras = (clone $comprasQuery)->sum('total');

        $utilidadNeta = $totalVentas - $totalGastos;

        $ventasPorSucursal = (clone $ventasQuery)
            ->selectRaw('sucursal_id, sum(valor_total) as total, count(*) as notas')
            ->groupBy('sucursal_id')
            ->with('sucursal')
            ->get();

        $gastosPorCategoria = (clone $gastosQuery)
            ->selectRaw('categoria, sum(monto) as total, count(*) as cantidad')
            ->groupBy('categoria')
            ->orderByDesc('total')
            ->get();

        $comprasPorAlmacen = (clone $comprasQuery)
            ->with('almacen.sucursal')
            ->get()
            ->groupBy(fn ($c) => $c->almacen->nombre ?? '—')
            ->map(fn ($grupo) => ['total' => $grupo->sum('total'), 'cantidad' => $grupo->count()]);

        return view('reportes.index', compact(
            'desde', 'hasta', 'sucursalId', 'sucursales',
            'totalVentas', 'notasVentas', 'totalGastos', 'totalCompras', 'utilidadNeta',
            'ventasPorSucursal', 'gastosPorCategoria', 'comprasPorAlmacen'
        ));
    }
}
