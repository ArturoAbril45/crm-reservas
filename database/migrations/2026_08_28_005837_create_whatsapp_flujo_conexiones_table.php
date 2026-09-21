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
        Schema::create('whatsapp_flujo_conexiones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('nodo_origen_id')->constrained('whatsapp_flujo_nodos')->cascadeOnDelete();
            $table->foreignId('nodo_destino_id')->constrained('whatsapp_flujo_nodos')->cascadeOnDelete();
            $table->string('valor')->nullable();
            $table->string('etiqueta')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('whatsapp_flujo_conexiones');
    }
};
