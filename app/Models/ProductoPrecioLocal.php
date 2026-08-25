<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductoPrecioLocal extends Model
{
    protected $table = 'producto_precios_locales';

    protected $fillable = ['producto_id', 'almacen_id', 'precio'];

    public function producto()
    {
        return $this->belongsTo(Producto::class);
    }

    public function almacen()
    {
        return $this->belongsTo(Almacen::class);
    }
}
