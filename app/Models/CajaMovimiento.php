<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CajaMovimiento extends Model
{
    public const TIPOS = ['INGRESO', 'EGRESO', 'APERTURA', 'CIERRE'];

    protected $fillable = ['fecha', 'sucursal_id', 'tipo', 'concepto', 'monto', 'usuario_id', 'referencia'];

    protected $casts = [
        'fecha' => 'date',
        'monto' => 'decimal:2',
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
