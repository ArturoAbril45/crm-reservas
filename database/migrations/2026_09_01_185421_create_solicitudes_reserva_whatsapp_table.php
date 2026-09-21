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
        Schema::create('solicitudes_reserva_whatsapp', function (Blueprint $table) {
            $table->id();
            $table->string('numero');
            $table->string('nombre');
            $table->string('cedula');
            $table->string('foto_lateral')->nullable();
            $table->string('foto_posterior')->nullable();
            $table->string('celular')->nullable();
            $table->string('local');
            $table->string('cuarto');
            $table->string('semana_texto');
            $table->string('estado')->default('pendiente');
            $table->text('motivo_rechazo')->nullable();
            $table->string('prenda_comprobante')->nullable();
            $table->foreignId('revisada_por_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('revisada_en')->nullable();
            $table->timestamps();

            $table->index(['estado']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('solicitudes_reserva_whatsapp');
    }
};
