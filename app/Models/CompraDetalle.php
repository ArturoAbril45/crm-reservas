<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompraDetalle extends Model
{
    protected $fillable = [
        'compra_id', 'producto_id', 'presentacion', 'factor_presentacion',
        'cantidad_presentacion', 'cantidad', 'costo_presentacion', 'costo_unitario', 'total',
    ];

    public function compra()
    {
        return $this->belongsTo(Compra::class);
    }

    public function producto()
    {
        return $this->belongsTo(Producto::class);
    }
}
