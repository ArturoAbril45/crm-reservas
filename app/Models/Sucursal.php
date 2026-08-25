<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sucursal extends Model
{
    protected $table = 'sucursales';

    protected $fillable = ['nombre', 'direccion', 'telefono', 'activa'];

    protected $casts = [
        'activa' => 'boolean',
    ];

    public function almacenes()
    {
        return $this->hasMany(Almacen::class);
    }

    public function habitaciones()
    {
        return $this->hasMany(Habitacion::class);
    }
}
