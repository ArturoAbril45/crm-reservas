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
        Schema::create('whatsapp_flujo_nodos', function (Blueprint $table) {
            $table->id();
            $table->string('clave')->unique();
            $table->string('tipo');
            $table->string('titulo');
            $table->text('mensaje')->nullable();
            $table->string('campo_destino')->nullable();
            $table->integer('pos_x')->default(0);
            $table->integer('pos_y')->default(0);
            $table->boolean('editable')->default(true);
            $table->boolean('eliminable')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('whatsapp_flujo_nodos');
    }
};
