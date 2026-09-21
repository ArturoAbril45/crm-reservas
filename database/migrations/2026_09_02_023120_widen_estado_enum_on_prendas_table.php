<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * SQLite guarda el enum de "estado" como un CHECK constraint que no se puede
     * alterar in-place, así que reconstruimos la tabla con "estado" como string
     * libre para poder agregar el estado intermedio "confirmada" (Pendiente ->
     * Confirmada -> Devuelta) sin depender de doctrine/dbal.
     */
    public function up(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            Schema::table('prendas', function (Blueprint $table) {
                $table->string('estado')->default('pendiente')->change();
            });

            return;
        }

        Schema::create('prendas_nueva', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sucursal_id')->constrained('sucursales')->cascadeOnDelete();
            $table->string('descripcion');
            $table->string('cliente')->nullable();
            $table->string('estado')->default('pendiente');
            $table->timestamp('devuelta_at')->nullable();
            $table->timestamps();
            $table->string('foto')->nullable();
        });

        DB::statement('INSERT INTO prendas_nueva (id, sucursal_id, descripcion, cliente, estado, devuelta_at, created_at, updated_at, foto)
            SELECT id, sucursal_id, descripcion, cliente, estado, devuelta_at, created_at, updated_at, foto FROM prendas');

        Schema::drop('prendas');
        Schema::rename('prendas_nueva', 'prendas');
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            Schema::table('prendas', function (Blueprint $table) {
                $table->enum('estado', ['pendiente', 'devuelta'])->default('pendiente')->change();
            });

            return;
        }

        DB::statement("UPDATE prendas SET estado = 'pendiente' WHERE estado NOT IN ('pendiente', 'devuelta')");

        Schema::create('prendas_vieja', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sucursal_id')->constrained('sucursales')->cascadeOnDelete();
            $table->string('descripcion');
            $table->string('cliente')->nullable();
            $table->enum('estado', ['pendiente', 'devuelta'])->default('pendiente');
            $table->timestamp('devuelta_at')->nullable();
            $table->timestamps();
            $table->string('foto')->nullable();
        });

        DB::statement('INSERT INTO prendas_vieja (id, sucursal_id, descripcion, cliente, estado, devuelta_at, created_at, updated_at, foto)
            SELECT id, sucursal_id, descripcion, cliente, estado, devuelta_at, created_at, updated_at, foto FROM prendas');

        Schema::drop('prendas');
        Schema::rename('prendas_vieja', 'prendas');
    }
};
