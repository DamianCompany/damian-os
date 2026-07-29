<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('investiga_solicitudes', function (Blueprint $table): void {
            $table->string('etapa_actual', 30)->default('solicitud')->after('estado');
            $table->unsignedTinyInteger('avance')->default(10)->after('etapa_actual');

            $table->text('pregunta_principal')->nullable();
            $table->text('objetivo_general')->nullable();
            $table->json('objetivos_especificos')->nullable();
            $table->text('beneficiarios')->nullable();
            $table->text('alcance')->nullable();
            $table->text('criterios_exito')->nullable();
            $table->json('definicion_especializada')->nullable();

            $table->string('factibilidad', 40)->nullable();
            $table->text('metodologia_resumida')->nullable();
            $table->json('recursos')->nullable();
            $table->json('actividades')->nullable();
            $table->date('fecha_inicio')->nullable();
            $table->date('fecha_fin_estimada')->nullable();
            $table->decimal('presupuesto_estimado', 12, 2)->nullable();
            $table->decimal('contingencia', 12, 2)->nullable();
            $table->json('riesgos')->nullable();

            $table->json('datasets')->nullable();
            $table->json('experimentos')->nullable();

            $table->string('metodo_analisis', 40)->nullable();
            $table->text('resultado_principal')->nullable();
            $table->text('limitaciones')->nullable();
            $table->json('entregables')->nullable();
            $table->string('propiedad_resultado', 30)->nullable();
            $table->string('permiso_publicacion', 30)->nullable();
            $table->date('fecha_cierre')->nullable();
            $table->json('carpetas_drive')->nullable();

            $table->index(['etapa_actual', 'estado']);
        });
    }

    public function down(): void
    {
        Schema::table('investiga_solicitudes', function (Blueprint $table): void {
            $table->dropIndex(['etapa_actual', 'estado']);
            $table->dropColumn([
                'etapa_actual',
                'avance',
                'pregunta_principal',
                'objetivo_general',
                'objetivos_especificos',
                'beneficiarios',
                'alcance',
                'criterios_exito',
                'definicion_especializada',
                'factibilidad',
                'metodologia_resumida',
                'recursos',
                'actividades',
                'fecha_inicio',
                'fecha_fin_estimada',
                'presupuesto_estimado',
                'contingencia',
                'riesgos',
                'datasets',
                'experimentos',
                'metodo_analisis',
                'resultado_principal',
                'limitaciones',
                'entregables',
                'propiedad_resultado',
                'permiso_publicacion',
                'fecha_cierre',
                'carpetas_drive',
            ]);
        });
    }
};
