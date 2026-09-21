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
        Schema::table('whatsapp_conversaciones', function (Blueprint $table) {
            $table->foreignId('sucursal_id')->nullable()->after('celular')->constrained('sucursales')->nullOnDelete();
            $table->foreignId('habitacion_propuesta_id')->nullable()->after('sucursal_id')->constrained('habitaciones')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('whatsapp_conversaciones', function (Blueprint $table) {
            $table->dropConstrainedForeignId('sucursal_id');
            $table->dropConstrainedForeignId('habitacion_propuesta_id');
        });
    }
};
