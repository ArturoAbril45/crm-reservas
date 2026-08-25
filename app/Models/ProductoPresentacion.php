<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductoPresentacion extends Model
{
    protected $table = 'producto_presentaciones';

    protected $fillable = ['producto_id', 'nombre', 'factor', 'costo', 'precio', 'activo'];

    protected $casts = [
        'factor' => 'decimal:4',
        'costo' => 'decimal:2',
        'precio' => 'decimal:2',
        'activo' => 'boolean',
    ];

    public function producto()
    {
        return $this->belongsTo(Producto::class);
    }
}
