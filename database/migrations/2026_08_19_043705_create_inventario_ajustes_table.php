<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventario_ajustes', function (Blueprint $table) {
            $table->id();
            $table->date('fecha');
            $table->foreignId('almacen_id')->constrained('almacenes')->cascadeOnDelete();
            $table->foreignId('producto_id')->constrained()->cascadeOnDelete();
            $table->string('tipo')->default('SALDO_INICIAL');
            $table->integer('stock_anterior');
            $table->integer('stock_nuevo');
            $table->integer('diferencia');
            $table->string('detalle')->nullable();
            $table->foreignId('usuario_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventario_ajustes');
    }
};
