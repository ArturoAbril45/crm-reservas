<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('whatsapp_conversaciones', function (Blueprint $table) {
            $table->boolean('chat_manual')->default(false)->after('numero');
        });

        Schema::table('whatsapp_mensajes', function (Blueprint $table) {
            $table->string('origen')->default('cliente')->after('numero');
            $table->string('imagen')->nullable()->after('mensaje');
        });
    }

    public function down(): void
    {
        Schema::table('whatsapp_conversaciones', function (Blueprint $table) {
            $table->dropColumn('chat_manual');
        });

        Schema::table('whatsapp_mensajes', function (Blueprint $table) {
            $table->dropColumn(['origen', 'imagen']);
        });
    }
};
