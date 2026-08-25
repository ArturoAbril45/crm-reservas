<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Colapsa "productos" de una fila-por-almacén a un catálogo global (una fila por código),
 * moviendo el stock y el precio de cada fila duplicada a inventarios/producto_precios_locales,
 * y remapeando las referencias históricas (venta_detalles, compra_detalles, traspasos) al
 * producto canónico antes de borrar las filas duplicadas.
 */
return new class extends Migration
{
    public function up(): void
    {
        $productos = DB::table('productos')->orderBy('id')->get();

        $grupos = $productos->groupBy(fn ($p) => $p->codigo ?? ('__sin_codigo_' . $p->id));

        foreach ($grupos as $filas) {
            $canonico = $filas->sortBy('id')->first();

            // Inventario del propio canónico en su almacén original.
            DB::table('inventarios')->insert([
                'producto_id' => $canonico->id,
                'almacen_id'  => $canonico->almacen_id,
                'stock'       => $canonico->stock,
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);

            foreach ($filas as $fila) {
                if ($fila->id === $canonico->id) {
                    continue;
                }

                // Stock del duplicado -> inventario del canónico en el almacén del duplicado.
                DB::table('inventarios')->insert([
                    'producto_id' => $canonico->id,
                    'almacen_id'  => $fila->almacen_id,
                    'stock'       => $fila->stock,
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ]);

                // Si el precio del duplicado difiere del canónico, queda como precio de respaldo del local.
                if ((float) $fila->precio !== (float) $canonico->precio) {
                    DB::table('producto_precios_locales')->insert([
                        'producto_id' => $canonico->id,
                        'almacen_id'  => $fila->almacen_id,
                        'precio'      => $fila->precio,
                        'created_at'  => now(),
                        'updated_at'  => now(),
                    ]);
                }

                // Presentaciones del duplicado: si el canónico no tiene una con ese nombre, se agrega
                // como presentación global; si ya existe y el precio difiere, queda como override de local.
                $presentacionesCanonico = DB::table('producto_presentaciones')
                    ->where('producto_id', $canonico->id)
                    ->get()
                    ->keyBy('nombre');

                $presentacionesDuplicado = DB::table('producto_presentaciones')
                    ->where('producto_id', $fila->id)
                    ->get();

                foreach ($presentacionesDuplicado as $presentacion) {
                    $delCanonico = $presentacionesCanonico->get($presentacion->nombre);

                    if (! $delCanonico) {
                        DB::table('producto_presentaciones')->insert([
                            'producto_id' => $canonico->id,
                            'nombre'      => $presentacion->nombre,
                            'factor'      => $presentacion->factor,
                            'costo'       => $presentacion->costo,
                            'precio'      => $presentacion->precio,
                            'activo'      => $presentacion->activo,
                            'created_at'  => now(),
                            'updated_at'  => now(),
                        ]);
                    } elseif ((float) $presentacion->precio !== (float) $delCanonico->precio) {
                        DB::table('producto_presentacion_precios_locales')->insert([
                            'producto_id' => $canonico->id,
                            'almacen_id'  => $fila->almacen_id,
                            'presentacion' => $presentacion->nombre,
                            'precio'      => $presentacion->precio,
                            'created_at'  => now(),
                            'updated_at'  => now(),
                        ]);
                    }
                }

                DB::table('producto_presentaciones')->where('producto_id', $fila->id)->delete();

                // Remapear referencias históricas al producto canónico.
                DB::table('venta_detalles')->where('producto_id', $fila->id)->update(['producto_id' => $canonico->id]);
                DB::table('compra_detalles')->where('producto_id', $fila->id)->update(['producto_id' => $canonico->id]);
                DB::table('traspasos')->where('producto_id', $fila->id)->update(['producto_id' => $canonico->id]);

                DB::table('productos')->where('id', $fila->id)->delete();
            }
        }

        Schema::table('productos', function (Blueprint $table) {
            $table->dropForeign(['almacen_id']);
            $table->dropColumn(['almacen_id', 'stock']);
        });

        Schema::table('productos', function (Blueprint $table) {
            $table->unique('codigo');
        });
    }

    public function down(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->dropUnique(['codigo']);
        });

        Schema::table('productos', function (Blueprint $table) {
            $table->foreignId('almacen_id')->nullable()->after('id')->constrained('almacenes')->cascadeOnDelete();
            $table->integer('stock')->default(0)->after('codigo');
        });

        // No se reconstruyen las filas duplicadas originales: este down() es solo
        // para permitir revertir la forma de la tabla en desarrollo, no para
        // restaurar los datos exactos previos a la migración (usar el respaldo).
        foreach (DB::table('inventarios')->orderBy('producto_id')->get() as $inv) {
            DB::table('productos')->where('id', $inv->producto_id)->update([
                'almacen_id' => $inv->almacen_id,
                'stock'      => $inv->stock,
            ]);
        }
    }
};
