<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('servicio_tecnico_ordenes', function (Blueprint $table): void {
            $table->string('tipo_atencion', 20)->default('reparacion')->after('tipo_equipo');
        });
    }

    public function down(): void
    {
        Schema::table('servicio_tecnico_ordenes', function (Blueprint $table): void {
            $table->dropColumn('tipo_atencion');
        });
    }
};
