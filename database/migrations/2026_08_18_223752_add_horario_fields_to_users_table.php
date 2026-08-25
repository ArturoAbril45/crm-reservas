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
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('horario_activo')->default(false)->after('permisos');
            $table->string('hora_inicio', 5)->default('00:00')->after('horario_activo');
            $table->string('hora_fin', 5)->default('23:59')->after('hora_inicio');
            $table->string('dias_trabajo')->default('0,1,2,3,4,5,6')->after('hora_fin');
            $table->date('bloqueado_fecha')->nullable()->after('dias_trabajo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['horario_activo', 'hora_inicio', 'hora_fin', 'dias_trabajo', 'bloqueado_fecha']);
        });
    }
};
