<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Reserva extends Model
{
    protected $fillable = [
        'habitacion_id', 'semana', 'nombre', 'cedula', 'telefono',
        'deposito_estado', 'estado', 'observaciones', 'foto_cedula', 'foto_deposito',
        'prenda_id', 'usuario_id', 'solicitud_whatsapp_id',
    ];

    protected $casts = [
        'semana' => 'date',
    ];

    public function habitacion()
    {
        return $this->belongsTo(Habitacion::class);
    }

    public function prenda()
    {
        return $this->belongsTo(Prenda::class);
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }
}
