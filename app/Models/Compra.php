<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Compra extends Model
{
    protected $fillable = [
        'numero', 'fecha', 'proveedor', 'almacen_id', 'usuario_id',
        'forma_pago', 'estado_pago', 'comprobante_pago', 'pagado_en',
        'total', 'observaciones',
    ];

    protected $casts = [
        'fecha' => 'date',
        'pagado_en' => 'datetime',
        'total' => 'decimal:2',
    ];

    public function almacen()
    {
        return $this->belongsTo(Almacen::class);
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function detalles()
    {
        return $this->hasMany(CompraDetalle::class);
    }
}
