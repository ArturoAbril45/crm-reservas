<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventarioAjuste extends Model
{
    protected $fillable = [
        'fecha', 'almacen_id', 'producto_id', 'tipo',
        'stock_anterior', 'stock_nuevo', 'diferencia', 'detalle', 'usuario_id',
    ];

    protected $casts = [
        'fecha' => 'date',
    ];

    public function producto()
    {
        return $this->belongsTo(Producto::class);
    }

    public function almacen()
    {
        return $this->belongsTo(Almacen::class);
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }
}
