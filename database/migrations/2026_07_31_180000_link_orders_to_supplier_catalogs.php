<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('servicio_tecnico_ordenes', function (Blueprint $table): void {
            $table->foreignId('categoria_servicio_tecnico_id')
                ->nullable()
                ->after('tipo_equipo')
                ->constrained('categorias_proveedor_servicio_tecnico', indexName: 'orden_st_categoria_fk')
                ->nullOnDelete();
            $table->foreignId('marca_servicio_tecnico_id')
                ->nullable()
                ->after('marca')
                ->constrained('marcas_proveedor_servicio_tecnico', indexName: 'orden_st_marca_fk')
                ->nullOnDelete();
            $table->foreignId('proveedor_servicio_tecnico_id')
                ->nullable()
                ->after('marca_servicio_tecnico_id')
                ->constrained('proveedores_servicio_tecnico', indexName: 'orden_st_proveedor_fk')
                ->nullOnDelete();
        });

        Schema::table('dami_orders', function (Blueprint $table): void {
            $table->foreignId('proveedor_dami3d_id')
                ->nullable()
                ->after('filament_type')
                ->constrained('proveedores_dami3d', indexName: 'orden_d3d_proveedor_fk')
                ->nullOnDelete();
            $table->foreignId('producto_proveedor_dami3d_id')
                ->nullable()
                ->after('proveedor_dami3d_id')
                ->constrained('productos_proveedor_dami3d', indexName: 'orden_d3d_producto_fk')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('dami_orders', function (Blueprint $table): void {
            $table->dropForeign('orden_d3d_producto_fk');
            $table->dropForeign('orden_d3d_proveedor_fk');
            $table->dropColumn(['producto_proveedor_dami3d_id', 'proveedor_dami3d_id']);
        });

        Schema::table('servicio_tecnico_ordenes', function (Blueprint $table): void {
            $table->dropForeign('orden_st_proveedor_fk');
            $table->dropForeign('orden_st_marca_fk');
            $table->dropForeign('orden_st_categoria_fk');
            $table->dropColumn([
                'proveedor_servicio_tecnico_id',
                'marca_servicio_tecnico_id',
                'categoria_servicio_tecnico_id',
            ]);
        });
    }
};
