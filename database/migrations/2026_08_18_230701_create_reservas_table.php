<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('reservas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('habitacion_id')->constrained('habitaciones')->cascadeOnDelete();
            $table->date('semana');
            $table->string('nombre');
            $table->string('cedula');
            $table->string('telefono');
            $table->string('deposito_estado')->default('');
            $table->string('estado')->default('Confirmada');
            $table->text('observaciones')->nullable();
            $table->string('foto_cedula')->nullable();
            $table->string('foto_deposito')->nullable();
            $table->foreignId('prenda_id')->nullable()->constrained('prendas')->nullOnDelete();
            $table->foreignId('usuario_id')->constrained('users');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reservas');
    }
};
