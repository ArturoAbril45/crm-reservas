<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Producto extends Model
{
    public const TIPOS = ['Producto', 'Servicio'];

    protected $fillable = [
        'codigo', 'nombre', 'unidad', 'tipo_item', 'controla_stock',
        'stock_minimo', 'stock_minimo_presentacion', 'costo', 'precio',
    ];

    protected $casts = [
        'controla_stock' => 'boolean',
        'stock_minimo' => 'decimal:2',
    ];

    public function presentaciones()
    {
        return $this->hasMany(ProductoPresentacion::class);
    }

    public function inventarios()
    {
        return $this->hasMany(Inventario::class);
    }

    public function preciosLocales()
    {
        return $this->hasMany(ProductoPrecioLocal::class);
    }

    public function presentacionPreciosLocales()
    {
        return $this->hasMany(ProductoPresentacionPrecioLocal::class);
    }

    public function stockEnAlmacen(?int $almacenId): int
    {
        if (! $almacenId) {
            return 0;
        }

        return (int) ($this->inventarios->firstWhere('almacen_id', $almacenId)->stock ?? 0);
    }

    public function stockTotal(): int
    {
        return (int) $this->inventarios->sum('stock');
    }

    /**
     * Resuelve el precio de venta para un almacén (y opcionalmente una presentación)
     * siguiendo la misma jerarquía del sistema de referencia:
     * precio de la presentación en ese local -> precio propio de la presentación
     * -> precio general de respaldo del local -> precio global del producto.
     */
    public function precioEnAlmacen(?int $almacenId, ?string $presentacionNombre = null): float
    {
        if ($presentacionNombre && $almacenId) {
            $override = $this->presentacionPreciosLocales
                ->first(fn ($p) => $p->almacen_id === $almacenId && $p->presentacion === $presentacionNombre);

            if ($override) {
                return (float) $override->precio;
            }
        }

        if ($presentacionNombre) {
            $presentacion = $this->presentaciones->firstWhere('nombre', $presentacionNombre);

            if ($presentacion && $presentacion->precio !== null) {
                return (float) $presentacion->precio;
            }
        }

        if ($almacenId) {
            $general = $this->preciosLocales->firstWhere('almacen_id', $almacenId);

            if ($general) {
                return (float) $general->precio;
            }
        }

        return (float) $this->precio;
    }

    /**
     * Convierte el stock mínimo (expresado en la presentación de alerta,
     * ej. "5 Jabas de 12") a unidades base para poder compararlo contra stock.
     */
    public function stockMinimoEnUnidades(): float
    {
        if (! $this->stock_minimo) {
            return 0;
        }

        $factor = 1;

        if ($this->stock_minimo_presentacion) {
            $presentacion = $this->presentaciones->firstWhere('nombre', $this->stock_minimo_presentacion);
            $factor = $presentacion ? (float) $presentacion->factor : 1;
        }

        return (float) $this->stock_minimo * $factor;
    }

    public function bajoStockMinimo(?int $almacenId): bool
    {
        return $this->controla_stock && $this->stock_minimo > 0 && $this->stockEnAlmacen($almacenId) <= $this->stockMinimoEnUnidades();
    }
}
