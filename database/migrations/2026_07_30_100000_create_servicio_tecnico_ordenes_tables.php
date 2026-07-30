<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('servicio_tecnico_ordenes', function (Blueprint $table): void {
            $table->id();
            $table->string('codigo')->unique();
            $table->string('cliente');
            $table->string('documento_cliente', 20)->nullable();
            $table->string('telefono', 30);
            $table->string('tipo_equipo', 40);
            $table->string('marca')->nullable();
            $table->string('modelo')->nullable();
            $table->string('numero_serie')->nullable();
            $table->text('falla_reportada');
            $table->string('accesorios')->nullable();
            $table->string('condicion_visible', 20)->default('normal');
            $table->string('prioridad', 20)->default('normal');
            $table->date('fecha_solicitada')->nullable();
            $table->string('ubicacion_fisica')->nullable();

            $table->string('estado', 30)->default('ingresado');
            $table->string('etapa_actual', 30)->default('ingreso');
            $table->unsignedTinyInteger('avance')->default(10);

            $table->json('checklist_diagnostico')->nullable();
            $table->text('diagnostico')->nullable();
            $table->text('solucion_recomendada')->nullable();
            $table->string('resultado_tecnico', 30)->nullable();
            $table->json('repuestos')->nullable();
            $table->json('mano_obra')->nullable();
            $table->json('servicios_externos')->nullable();
            $table->decimal('costo_estimado', 12, 2)->nullable();
            $table->decimal('margen_porcentaje', 5, 2)->default(25);
            $table->decimal('precio_cotizado', 12, 2)->nullable();
            $table->date('fecha_entrega_estimada')->nullable();

            $table->string('decision_cliente', 30)->nullable();
            $table->string('canal_aprobacion', 30)->nullable();
            $table->dateTime('fecha_aprobacion')->nullable();
            $table->text('evidencia_aprobacion')->nullable();
            $table->dateTime('trabajo_iniciado_en')->nullable();
            $table->dateTime('trabajo_finalizado_en')->nullable();
            $table->unsignedInteger('tiempo_real_minutos')->nullable();
            $table->text('trabajo_realizado')->nullable();
            $table->json('material_usado')->nullable();
            $table->decimal('costo_real', 12, 2)->nullable();

            $table->string('resultado_prueba', 20)->nullable();
            $table->unsignedInteger('duracion_prueba_minutos')->nullable();
            $table->text('observacion_prueba')->nullable();
            $table->string('aviso_cliente_canal', 30)->nullable();
            $table->string('persona_recoge')->nullable();
            $table->string('documento_recoge', 20)->nullable();
            $table->string('estado_pago', 20)->default('pendiente');
            $table->boolean('repuestos_antiguos_entregados')->default(false);
            $table->dateTime('entregado_en')->nullable();
            $table->unsignedSmallInteger('garantia_dias')->nullable();
            $table->text('motivo_retorno')->nullable();
            $table->string('clasificacion_retorno', 20)->nullable();
            $table->text('solucion_retorno')->nullable();

            $table->foreignId('responsable_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('creado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->string('carpeta_drive_id')->nullable();
            $table->text('carpeta_drive_url')->nullable();
            $table->json('carpetas_drive')->nullable();
            $table->timestamps();

            $table->index(['estado', 'created_at']);
            $table->index(['tipo_equipo', 'numero_serie']);
            $table->index(['cliente', 'telefono']);
        });

        Schema::create('servicio_tecnico_archivos', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('orden_id')->constrained('servicio_tecnico_ordenes')->cascadeOnDelete();
            $table->string('etapa', 30)->default('ingreso');
            $table->string('nombre_original');
            $table->string('ruta_temporal')->nullable();
            $table->string('tipo_mime')->nullable();
            $table->unsignedBigInteger('tamano_bytes')->nullable();
            $table->string('archivo_drive_id')->nullable()->unique();
            $table->text('archivo_drive_url')->nullable();
            $table->timestamp('sincronizado_drive_en')->nullable();
            $table->timestamps();

            $table->index(['orden_id', 'etapa']);
        });

        Schema::create('servicio_tecnico_historial_estados', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('orden_id')->constrained('servicio_tecnico_ordenes')->cascadeOnDelete();
            $table->string('estado_anterior', 30)->nullable();
            $table->string('estado_nuevo', 30);
            $table->foreignId('cambiado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->text('nota')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['orden_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('servicio_tecnico_historial_estados');
        Schema::dropIfExists('servicio_tecnico_archivos');
        Schema::dropIfExists('servicio_tecnico_ordenes');
    }
};
