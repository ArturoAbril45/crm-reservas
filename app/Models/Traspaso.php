<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Traspaso extends Model
{
    protected $fillable = [
        'sucursal_origen_id',
        'sucursal_destino_id',
        'producto_id',
        'cantidad',
    ];

    public function sucursalOrigen()
    {
        return $this->belongsTo(Sucursal::class, 'sucursal_origen_id');
    }

    public function sucursalDestino()
    {
        return $this->belongsTo(Sucursal::class, 'sucursal_destino_id');
    }

    public function producto()
    {
        return $this->belongsTo(Producto::class);
    }
}
