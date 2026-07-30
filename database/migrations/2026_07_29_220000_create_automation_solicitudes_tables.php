<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('automation_solicitudes', function (Blueprint $table): void {
            $table->id();
            $table->string('codigo')->unique();
            $table->string('cliente');
            $table->string('contacto_nombre')->nullable();
            $table->string('contacto_medio')->nullable();
            $table->string('titulo');
            $table->string('tipo_servicio', 40);
            $table->text('necesidad');
            $table->string('resultado_esperado');
            $table->string('ubicacion')->nullable();
            $table->boolean('requiere_visita')->default(false);
            $table->decimal('presupuesto_referencial', 12, 2)->nullable();
            $table->date('fecha_requerida')->nullable();

            $table->string('estado', 30)->default('solicitud');
            $table->string('etapa_actual', 30)->default('solicitud');
            $table->unsignedTinyInteger('avance')->default(10);
            $table->string('prioridad', 20)->default('normal');

            $table->text('objetivo')->nullable();
            $table->text('proceso_actual')->nullable();
            $table->json('alcance_incluido')->nullable();
            $table->json('exclusiones')->nullable();
            $table->json('entregables')->nullable();
            $table->text('criterios_aceptacion')->nullable();
            $table->text('tecnologia_propuesta')->nullable();
            $table->text('restricciones')->nullable();
            $table->json('requerimientos_tecnicos')->nullable();

            $table->string('factibilidad', 40)->nullable();
            $table->json('actividades')->nullable();
            $table->json('materiales')->nullable();
            $table->json('servicios_externos')->nullable();
            $table->decimal('costo_estimado', 12, 2)->nullable();
            $table->decimal('contingencia_porcentaje', 5, 2)->default(10);
            $table->decimal('margen_porcentaje', 5, 2)->default(25);
            $table->decimal('precio_venta', 12, 2)->nullable();
            $table->decimal('costo_real', 12, 2)->nullable();
            $table->string('forma_pago', 40)->nullable();
            $table->date('fecha_inicio')->nullable();
            $table->date('fecha_fin_estimada')->nullable();
            $table->date('fecha_aprobacion')->nullable();
            $table->string('canal_aprobacion')->nullable();
            $table->text('evidencia_aprobacion')->nullable();

            $table->json('tareas')->nullable();
            $table->json('cambios')->nullable();
            $table->json('pruebas')->nullable();
            $table->date('fecha_instalacion')->nullable();
            $table->string('lugar_instalacion')->nullable();
            $table->text('observacion_instalacion')->nullable();
            $table->json('capacitaciones')->nullable();
            $table->date('fecha_entrega')->nullable();
            $table->text('observaciones_entrega')->nullable();
            $table->unsignedSmallInteger('garantia_dias')->nullable();
            $table->json('incidencias_soporte')->nullable();

            $table->foreignId('responsable_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('creado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->string('carpeta_drive_id')->nullable();
            $table->text('carpeta_drive_url')->nullable();
            $table->json('carpetas_drive')->nullable();
            $table->timestamps();

            $table->index(['estado', 'created_at']);
            $table->index(['etapa_actual', 'prioridad']);
            $table->index(['tipo_servicio', 'cliente']);
        });

        Schema::create('automation_archivos', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('solicitud_id')->constrained('automation_solicitudes')->cascadeOnDelete();
            $table->string('etapa', 30)->default('solicitud');
            $table->string('categoria', 40)->default('adjunto');
            $table->string('nombre_original');
            $table->string('ruta_temporal')->nullable();
            $table->string('tipo_mime')->nullable();
            $table->unsignedBigInteger('tamano_bytes')->nullable();
            $table->string('archivo_drive_id')->nullable()->unique();
            $table->text('archivo_drive_url')->nullable();
            $table->timestamp('sincronizado_drive_en')->nullable();
            $table->timestamps();

            $table->index(['solicitud_id', 'etapa']);
        });

        Schema::create('automation_historial_estados', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('solicitud_id')->constrained('automation_solicitudes')->cascadeOnDelete();
            $table->string('estado_anterior', 30)->nullable();
            $table->string('estado_nuevo', 30);
            $table->foreignId('cambiado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->text('nota')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['solicitud_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('automation_historial_estados');
        Schema::dropIfExists('automation_archivos');
        Schema::dropIfExists('automation_solicitudes');
    }
};
