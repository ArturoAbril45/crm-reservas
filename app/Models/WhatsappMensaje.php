<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WhatsappMensaje extends Model
{
    protected $fillable = ['numero', 'origen', 'nombre', 'mensaje', 'imagen', 'respuesta'];
}
