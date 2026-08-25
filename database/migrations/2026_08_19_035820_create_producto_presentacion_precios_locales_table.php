<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('producto_presentacion_precios_locales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('producto_id')->constrained()->cascadeOnDelete();
            $table->foreignId('almacen_id')->constrained('almacenes')->cascadeOnDelete();
            $table->string('presentacion');
            $table->decimal('precio', 10, 2);
            $table->timestamps();

            $table->unique(['producto_id', 'almacen_id', 'presentacion']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('producto_presentacion_precios_locales');
    }
};
