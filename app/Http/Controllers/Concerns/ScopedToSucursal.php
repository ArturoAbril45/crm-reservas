<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Sucursal;
use Illuminate\Http\Request;

trait ScopedToSucursal
{
    private function sucursalActual(Request $request): ?Sucursal
    {
        $id = $request->session()->get('sucursal_actual_id');

        return $id ? Sucursal::find($id) : Sucursal::orderBy('nombre')->first();
    }
}
