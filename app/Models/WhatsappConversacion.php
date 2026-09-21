<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WhatsappConversacion extends Model
{
    protected $table = 'whatsapp_conversaciones';

    protected $fillable = [
        'numero', 'chat_manual', 'paso', 'nodo_actual_id', 'nombre', 'cedula', 'foto_lateral', 'foto_posterior', 'celular',
        'semana_texto', 'local_texto', 'cuarto_texto', 'prenda_comprobante', 'solicitud_pendiente_id',
        'opciones_habitacion', 'sucursal_id', 'habitacion_propuesta_id',
    ];

    protected $casts = [
        'opciones_habitacion' => 'array',
        'chat_manual' => 'boolean',
    ];

    public function sucursal()
    {
        return $this->belongsTo(Sucursal::class);
    }

    public function habitacionPropuesta()
    {
        return $this->belongsTo(Habitacion::class, 'habitacion_propuesta_id');
    }

    public function nodoActual()
    {
        return $this->belongsTo(WhatsappFlujoNodo::class, 'nodo_actual_id');
    }

    public function solicitudPendiente()
    {
        return $this->belongsTo(SolicitudReservaWhatsapp::class, 'solicitud_pendiente_id');
    }
}
