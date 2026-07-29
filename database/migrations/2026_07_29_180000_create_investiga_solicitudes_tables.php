<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('investiga_solicitudes', function (Blueprint $table): void {
            $table->id();
            $table->string('codigo')->unique();
            $table->string('solicitante');
            $table->string('titulo');
            $table->string('sector', 60);
            $table->string('tipo_proyecto', 40);
            $table->text('problema_necesidad');
            $table->string('resultado_esperado');
            $table->date('fecha_requerida')->nullable();
            $table->decimal('presupuesto_referencial', 12, 2)->nullable();
            $table->string('confidencialidad', 20)->default('normal');
            $table->string('estado', 30)->default('idea_registrada');
            $table->foreignId('responsable_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('creado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->string('carpeta_drive_id')->nullable();
            $table->text('carpeta_drive_url')->nullable();
            $table->timestamps();

            $table->index(['estado', 'created_at']);
            $table->index(['tipo_proyecto', 'sector']);
        });

        Schema::create('investiga_archivos', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('solicitud_id')->constrained('investiga_solicitudes')->cascadeOnDelete();
            $table->string('tipo', 30)->default('adjunto');
            $table->string('nombre_original');
            $table->string('ruta_temporal')->nullable();
            $table->string('tipo_mime')->nullable();
            $table->unsignedBigInteger('tamano_bytes')->nullable();
            $table->string('archivo_drive_id')->nullable()->unique();
            $table->text('archivo_drive_url')->nullable();
            $table->timestamp('sincronizado_drive_en')->nullable();
            $table->timestamps();

            $table->index(['solicitud_id', 'tipo']);
        });

        Schema::create('investiga_historial_estados', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('solicitud_id')->constrained('investiga_solicitudes')->cascadeOnDelete();
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
        Schema::dropIfExists('investiga_historial_estados');
        Schema::dropIfExists('investiga_archivos');
        Schema::dropIfExists('investiga_solicitudes');
    }
};
