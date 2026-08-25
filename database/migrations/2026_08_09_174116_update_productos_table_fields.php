<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->renameColumn('sku', 'codigo');
            $table->string('unidad')->default('Unidad')->after('nombre');
            $table->decimal('costo', 10, 2)->default(0)->after('unidad');
        });
    }

    public function down(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->renameColumn('codigo', 'sku');
            $table->dropColumn(['unidad', 'costo']);
        });
    }
};
