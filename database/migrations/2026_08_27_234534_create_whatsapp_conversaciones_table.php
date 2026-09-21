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
        Schema::create('whatsapp_conversaciones', function (Blueprint $table) {
            $table->id();
            $table->string('numero')->unique();
            $table->string('paso')->default('inicio');
            $table->string('nombre')->nullable();
            $table->string('cedula')->nullable();
            $table->string('foto_lateral')->nullable();
            $table->string('foto_posterior')->nullable();
            $table->string('celular')->nullable();
            $table->text('opciones_habitacion')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('whatsapp_conversaciones');
    }
};
