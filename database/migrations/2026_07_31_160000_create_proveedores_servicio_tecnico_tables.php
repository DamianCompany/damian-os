<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categorias_proveedor_servicio_tecnico', function (Blueprint $table): void {
            $table->id();
            $table->string('nombre')->unique();
            $table->unsignedSmallInteger('orden')->default(0);
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        Schema::create('marcas_proveedor_servicio_tecnico', function (Blueprint $table): void {
            $table->id();
            $table->string('nombre')->unique();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        Schema::create('proveedores_servicio_tecnico', function (Blueprint $table): void {
            $table->id();
            $table->string('codigo')->unique();
            $table->string('tipo', 30)->default('empresa');
            $table->string('razon_social');
            $table->string('nombre_comercial')->nullable();
            $table->string('tipo_documento', 20)->nullable();
            $table->string('numero_documento', 30)->nullable();
            $table->string('contacto_nombre')->nullable();
            $table->string('whatsapp', 30)->nullable();
            $table->string('telefono', 30)->nullable();
            $table->string('correo')->nullable();
            $table->string('direccion')->nullable();
            $table->text('productos_principales')->nullable();
            $table->unsignedSmallInteger('entrega_promedio_dias')->nullable();
            $table->string('forma_pago', 30)->nullable();
            $table->boolean('emite_factura')->default(false);
            $table->boolean('principal')->default(false);
            $table->string('estado', 20)->default('evaluacion');
            $table->text('notas')->nullable();
            $table->foreignId('creado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('actualizado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tipo_documento', 'numero_documento'], 'proveedores_st_documento_unique');
            $table->index(['estado', 'razon_social'], 'proveedores_st_estado_nombre_index');
        });

        Schema::create('proveedor_categoria_servicio_tecnico', function (Blueprint $table): void {
            $table->foreignId('proveedor_id')->constrained('proveedores_servicio_tecnico')->cascadeOnDelete();
            $table->foreignId('categoria_id')->constrained('categorias_proveedor_servicio_tecnico')->restrictOnDelete();
            $table->primary(['proveedor_id', 'categoria_id']);
        });

        Schema::create('proveedor_marca_servicio_tecnico', function (Blueprint $table): void {
            $table->foreignId('proveedor_id')->constrained('proveedores_servicio_tecnico')->cascadeOnDelete();
            $table->foreignId('marca_id')->constrained('marcas_proveedor_servicio_tecnico')->restrictOnDelete();
            $table->primary(['proveedor_id', 'marca_id']);
        });

        $ahora = now();
        $categorias = [
            'Agricultura',
            'Forestal',
            'Equipos',
            'Industrias metálicas',
            'Herramientas eléctricas',
            'Herramientas manuales',
            'Consumibles',
            'Accesorios y repuestos',
            'Electrónica e IoT',
            'Lubricantes',
            'Seguridad y EPP',
        ];

        foreach ($categorias as $indice => $nombre) {
            DB::table('categorias_proveedor_servicio_tecnico')->insert([
                'nombre' => $nombre,
                'orden' => $indice + 1,
                'activo' => true,
                'created_at' => $ahora,
                'updated_at' => $ahora,
            ]);
        }

        foreach (['Stihl', 'Shindaiwa', 'Royal Condor', 'Husqvarna', 'Jacto', 'Vistony', 'JAFID', 'HONDA', 'RUZZO'] as $nombre) {
            DB::table('marcas_proveedor_servicio_tecnico')->insert([
                'nombre' => $nombre,
                'activo' => true,
                'created_at' => $ahora,
                'updated_at' => $ahora,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('proveedor_marca_servicio_tecnico');
        Schema::dropIfExists('proveedor_categoria_servicio_tecnico');
        Schema::dropIfExists('proveedores_servicio_tecnico');
        Schema::dropIfExists('marcas_proveedor_servicio_tecnico');
        Schema::dropIfExists('categorias_proveedor_servicio_tecnico');
    }
};
