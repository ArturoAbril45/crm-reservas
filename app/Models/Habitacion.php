<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Habitacion extends Model
{
    protected $table = 'habitaciones';

    protected $fillable = ['sucursal_id', 'numero', 'estado'];

    public function sucursal()
    {
        return $this->belongsTo(Sucursal::class);
    }
}
