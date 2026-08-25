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
        Schema::table('productos', function (Blueprint $table) {
            $table->string('tipo_item')->default('Producto')->after('unidad');
            $table->boolean('controla_stock')->default(true)->after('tipo_item');
            $table->decimal('stock_minimo', 10, 2)->default(0)->after('stock');
            $table->string('stock_minimo_presentacion')->nullable()->after('stock_minimo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->dropColumn(['tipo_item', 'controla_stock', 'stock_minimo', 'stock_minimo_presentacion']);
        });
    }
};
