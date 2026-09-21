<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Prenda extends Model
{
    protected $fillable = ['sucursal_id', 'descripcion', 'cliente', 'estado', 'devuelta_at', 'foto'];

    protected $casts = [
        'devuelta_at' => 'datetime',
    ];

    public function sucursal()
    {
        return $this->belongsTo(Sucursal::class);
    }

    // La reserva que dejó esta prenda como garantía (para poder mostrar en qué
    // semana y qué habitación quedó, en la lista de "Prendas pendientes").
    public function reserva()
    {
        return $this->hasOne(Reserva::class);
    }
}
