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
            $table->string('semana_texto')->nullable()->after('celular');
            $table->string('local_texto')->nullable()->after('semana_texto');
            $table->string('cuarto_texto')->nullable()->after('local_texto');
            $table->string('prenda_comprobante')->nullable()->after('cuarto_texto');
            $table->foreignId('solicitud_pendiente_id')->nullable()->after('prenda_comprobante')
                ->constrained('solicitudes_reserva_whatsapp')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('whatsapp_conversaciones', function (Blueprint $table) {
            $table->dropConstrainedForeignId('solicitud_pendiente_id');
            $table->dropColumn(['semana_texto', 'local_texto', 'cuarto_texto', 'prenda_comprobante']);
        });
    }
};
