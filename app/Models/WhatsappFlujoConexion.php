<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WhatsappFlujoConexion extends Model
{
    protected $table = 'whatsapp_flujo_conexiones';

    protected $fillable = ['nodo_origen_id', 'nodo_destino_id', 'valor', 'etiqueta'];

    public function origen()
    {
        return $this->belongsTo(WhatsappFlujoNodo::class, 'nodo_origen_id');
    }

    public function destino()
    {
        return $this->belongsTo(WhatsappFlujoNodo::class, 'nodo_destino_id');
    }
}
