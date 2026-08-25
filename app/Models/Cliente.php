<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cliente extends Model
{
    protected $fillable = [
        'nombre_completo',
        'cedula',
        'correo',
        'celular',
        'foto_cedula_frontal',
        'foto_cedula_trasera',
    ];
}
