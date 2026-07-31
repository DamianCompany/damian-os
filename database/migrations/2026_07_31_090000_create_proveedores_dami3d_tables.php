<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categorias_proveedor_dami3d', function (Blueprint $table): void {
            $table->id(); $table->string('nombre')->unique(); $table->unsignedSmallInteger('orden')->default(0);
            $table->boolean('activo')->default(true); $table->timestamps();
        });
        Schema::create('marcas_proveedor_dami3d', function (Blueprint $table): void {
            $table->id(); $table->string('nombre')->unique(); $table->boolean('activo')->default(true); $table->timestamps();
        });
        Schema::create('proveedores_dami3d', function (Blueprint $table): void {
            $table->id(); $table->string('codigo')->unique(); $table->string('tipo', 30);
            $table->string('razon_social'); $table->string('nombre_comercial')->nullable();
            $table->string('tipo_documento', 20)->nullable(); $table->string('numero_documento', 30)->nullable();
            $table->string('contacto_nombre')->nullable(); $table->string('contacto_cargo')->nullable();
            $table->string('telefono', 30)->nullable(); $table->string('telefono_secundario', 30)->nullable();
            $table->string('whatsapp', 30)->nullable(); $table->string('correo_ventas')->nullable();
            $table->string('correo_facturacion')->nullable(); $table->string('sitio_web')->nullable();
            $table->string('pais')->default('Perú'); $table->string('departamento')->nullable();
            $table->string('provincia')->nullable(); $table->string('distrito')->nullable();
            $table->string('direccion')->nullable(); $table->string('referencia')->nullable();
            $table->string('maps_url')->nullable(); $table->string('moneda', 10)->default('PEN');
            $table->string('forma_pago', 30)->nullable(); $table->string('condicion_pago', 30)->nullable();
            $table->unsignedSmallInteger('dias_credito')->nullable(); $table->decimal('compra_minima', 12, 2)->nullable();
            $table->unsignedSmallInteger('entrega_promedio_dias')->nullable(); $table->decimal('costo_envio', 12, 2)->nullable();
            $table->boolean('emite_factura')->default(false); $table->boolean('emite_boleta')->default(false);
            $table->boolean('ofrece_garantia')->default(false); $table->text('condiciones_garantia')->nullable();
            $table->string('banco')->nullable(); $table->string('tipo_cuenta', 20)->nullable();
            $table->string('numero_cuenta')->nullable(); $table->string('cci')->nullable();
            $table->string('titular_cuenta')->nullable(); $table->string('yape')->nullable(); $table->string('plin')->nullable();
            $table->decimal('calificacion', 3, 2)->default(0); $table->string('estado', 20)->default('evaluacion');
            $table->boolean('principal')->default(false); $table->text('notas')->nullable(); $table->text('motivo_estado')->nullable();
            $table->foreignId('creado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('actualizado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->string('carpeta_drive_id')->nullable(); $table->text('carpeta_drive_url')->nullable();
            $table->timestamps(); $table->softDeletes();
            $table->unique(['tipo_documento', 'numero_documento']);
            $table->index(['estado', 'razon_social']);
        });
        Schema::create('proveedor_categoria_dami3d', function (Blueprint $table): void {
            $table->foreignId('proveedor_id')->constrained('proveedores_dami3d')->cascadeOnDelete();
            $table->foreignId('categoria_id')->constrained('categorias_proveedor_dami3d')->restrictOnDelete();
            $table->primary(['proveedor_id', 'categoria_id']);
        });
        Schema::create('proveedor_marca_dami3d', function (Blueprint $table): void {
            $table->foreignId('proveedor_id')->constrained('proveedores_dami3d')->cascadeOnDelete();
            $table->foreignId('marca_id')->constrained('marcas_proveedor_dami3d')->restrictOnDelete();
            $table->boolean('principal')->default(false); $table->primary(['proveedor_id', 'marca_id']);
        });
        Schema::create('productos_proveedor_dami3d', function (Blueprint $table): void {
            $table->id(); $table->foreignId('proveedor_id')->constrained('proveedores_dami3d')->cascadeOnDelete();
            $table->foreignId('categoria_id')->nullable()->constrained('categorias_proveedor_dami3d')->nullOnDelete();
            $table->foreignId('marca_id')->nullable()->constrained('marcas_proveedor_dami3d')->nullOnDelete();
            $table->string('nombre'); $table->string('presentacion')->nullable(); $table->string('unidad_medida', 20)->nullable();
            $table->decimal('precio_referencial', 12, 2); $table->string('moneda', 10)->default('PEN');
            $table->boolean('igv_incluido')->default(true); $table->decimal('cantidad_minima', 10, 2)->nullable();
            $table->string('disponibilidad', 20)->default('consultar'); $table->unsignedSmallInteger('entrega_dias')->nullable();
            $table->text('producto_url')->nullable(); $table->text('observaciones')->nullable();
            $table->timestamp('precio_actualizado_en')->useCurrent(); $table->boolean('activo')->default(true); $table->timestamps();
            $table->index(['proveedor_id', 'nombre']);
        });
        Schema::create('historial_precios_proveedor_dami3d', function (Blueprint $table): void {
            $table->id(); $table->foreignId('producto_id')->constrained('productos_proveedor_dami3d')->cascadeOnDelete();
            $table->decimal('precio_anterior', 12, 2)->nullable(); $table->decimal('precio_nuevo', 12, 2);
            $table->string('moneda', 10); $table->foreignId('registrado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();
        });
        Schema::create('evaluaciones_proveedor_dami3d', function (Blueprint $table): void {
            $table->id(); $table->foreignId('proveedor_id')->constrained('proveedores_dami3d')->cascadeOnDelete();
            foreach (['precio', 'calidad', 'entrega', 'atencion', 'cantidades', 'estado_producto', 'pago', 'reclamos'] as $campo) $table->unsignedTinyInteger($campo);
            $table->decimal('promedio', 3, 2); $table->text('comentario')->nullable();
            $table->foreignId('evaluado_por')->nullable()->constrained('users')->nullOnDelete(); $table->timestamps();
        });
        Schema::create('incidencias_proveedor_dami3d', function (Blueprint $table): void {
            $table->id(); $table->foreignId('proveedor_id')->constrained('proveedores_dami3d')->cascadeOnDelete();
            $table->string('tipo', 30); $table->date('fecha'); $table->text('descripcion');
            $table->text('solucion')->nullable(); $table->string('estado', 20)->default('pendiente'); $table->date('fecha_cierre')->nullable();
            $table->foreignId('reportado_por')->nullable()->constrained('users')->nullOnDelete(); $table->timestamps();
        });
        Schema::create('documentos_proveedor_dami3d', function (Blueprint $table): void {
            $table->id(); $table->foreignId('proveedor_id')->constrained('proveedores_dami3d')->cascadeOnDelete();
            $table->string('tipo', 30); $table->string('nombre_original'); $table->string('ruta_temporal')->nullable();
            $table->string('archivo_drive_id')->nullable(); $table->text('archivo_drive_url')->nullable();
            $table->foreignId('cargado_por')->nullable()->constrained('users')->nullOnDelete(); $table->timestamps();
        });
        Schema::create('actividad_proveedor_dami3d', function (Blueprint $table): void {
            $table->id(); $table->foreignId('proveedor_id')->constrained('proveedores_dami3d')->cascadeOnDelete();
            $table->string('accion'); $table->text('detalle')->nullable();
            $table->foreignId('usuario_id')->nullable()->constrained('users')->nullOnDelete(); $table->timestamp('created_at')->useCurrent();
        });

        $ahora = now();
        foreach (['Filamentos FDM', 'Resinas', 'Pintado y acabado', 'Adhesivos', 'Moldes y figuras', 'Velas y cera', 'Limpieza y seguridad', 'Repuestos 3D', 'Empaques', 'Otros'] as $orden => $nombre) {
            DB::table('categorias_proveedor_dami3d')->insert(['nombre' => $nombre, 'orden' => $orden + 1, 'activo' => true, 'created_at' => $ahora, 'updated_at' => $ahora]);
        }
    }

    public function down(): void
    {
        foreach (['actividad_proveedor_dami3d','documentos_proveedor_dami3d','incidencias_proveedor_dami3d','evaluaciones_proveedor_dami3d','historial_precios_proveedor_dami3d','productos_proveedor_dami3d','proveedor_marca_dami3d','proveedor_categoria_dami3d','proveedores_dami3d','marcas_proveedor_dami3d','categorias_proveedor_dami3d'] as $tabla) Schema::dropIfExists($tabla);
    }
};
