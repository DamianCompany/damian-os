<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('servicio_tecnico_ordenes', function (Blueprint $table): void {
            $table->json('conceptos_mantenimiento')->nullable()->after('servicios_externos');
            $table->decimal('base_imponible', 12, 2)->nullable()->after('precio_cotizado');
            $table->decimal('igv_incluido', 12, 2)->nullable()->after('base_imponible');
            $table->timestamp('cotizacion_generada_en')->nullable()->after('fecha_aprobacion');
            $table->string('cotizacion_drive_id')->nullable()->after('cotizacion_generada_en');
            $table->text('cotizacion_drive_url')->nullable()->after('cotizacion_drive_id');
        });
    }

    public function down(): void
    {
        Schema::table('servicio_tecnico_ordenes', function (Blueprint $table): void {
            $table->dropColumn([
                'conceptos_mantenimiento',
                'base_imponible',
                'igv_incluido',
                'cotizacion_generada_en',
                'cotizacion_drive_id',
                'cotizacion_drive_url',
            ]);
        });
    }
};
