<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clientes', function (Blueprint $table) {
            $table->id();
            $table->string('nombre_completo');
            $table->string('cedula')->unique();
            $table->string('correo')->nullable();
            $table->string('celular')->nullable();
            $table->string('foto_cedula_frontal')->nullable();
            $table->string('foto_cedula_trasera')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clientes');
    }
};
