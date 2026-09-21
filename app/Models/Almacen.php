<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Almacen extends Model
{
    protected $table = 'almacenes';

    protected $fillable = ['sucursal_id', 'nombre', 'ubicacion'];

    public function sucursal()
    {
        return $this->belongsTo(Sucursal::class);
    }

    public function inventarios()
    {
        return $this->hasMany(Inventario::class);
    }

    public function compras()
    {
        return $this->hasMany(Compra::class);
    }
}
