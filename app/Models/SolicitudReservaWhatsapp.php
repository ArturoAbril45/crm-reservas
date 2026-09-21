<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SolicitudReservaWhatsapp extends Model
{
    protected $table = 'solicitudes_reserva_whatsapp';

    protected $fillable = [
        'numero', 'nombre', 'cedula', 'foto_lateral', 'foto_posterior', 'celular',
        'local', 'cuarto', 'cuartos_sugeridos', 'semana_texto', 'estado', 'motivo_rechazo',
        'prenda_comprobante', 'revisada_por_id', 'revisada_en',
    ];

    protected $casts = [
        'revisada_en' => 'datetime',
        'cuartos_sugeridos' => 'array',
    ];

    public const PENDIENTE = 'pendiente';
    public const APROBADA_ESPERANDO_PRENDA = 'aprobada_esperando_prenda';
    public const RECHAZADA = 'rechazada';
    public const PRENDA_EN_REVISION = 'prenda_en_revision';
    public const CONFIRMADA = 'confirmada';

    public function revisadaPor()
    {
        return $this->belongsTo(User::class, 'revisada_por_id');
    }
}
