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
        Schema::create('compras', function (Blueprint $table) {
            $table->id();
            $table->string('numero')->unique();
            $table->date('fecha');
            $table->string('proveedor')->nullable();
            $table->foreignId('almacen_id')->constrained('almacenes');
            $table->foreignId('usuario_id')->constrained('users');
            $table->string('forma_pago');
            $table->string('estado_pago')->default('Cancelado');
            $table->string('comprobante_pago')->nullable();
            $table->timestamp('pagado_en')->nullable();
            $table->decimal('total', 10, 2)->default(0);
            $table->text('observaciones')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('compras');
    }
};
