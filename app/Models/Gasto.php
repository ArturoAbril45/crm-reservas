<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Gasto extends Model
{
    protected $fillable = ['fecha', 'sucursal_id', 'categoria', 'descripcion', 'forma_pago', 'monto', 'usuario_id', 'es_general'];

    protected $casts = [
        'fecha' => 'date',
        'monto' => 'decimal:2',
        'es_general' => 'boolean',
    ];

    public function sucursal()
    {
        return $this->belongsTo(Sucursal::class);
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }
}
