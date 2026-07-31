<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProveedorServicioTecnico extends Model
{
    use SoftDeletes;

    protected $table = 'proveedores_servicio_tecnico';
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'emite_factura' => 'boolean',
            'principal' => 'boolean',
        ];
    }

    public function categorias(): BelongsToMany
    {
        return $this->belongsToMany(
            CategoriaProveedorServicioTecnico::class,
            'proveedor_categoria_servicio_tecnico',
            'proveedor_id',
            'categoria_id',
        );
    }

    public function marcas(): BelongsToMany
    {
        return $this->belongsToMany(
            MarcaProveedorServicioTecnico::class,
            'proveedor_marca_servicio_tecnico',
            'proveedor_id',
            'marca_id',
        );
    }

    protected static function booted(): void
    {
        static::creating(function (self $proveedor): void {
            $anio = now()->year;
            $ultimoId = self::withTrashed()->whereYear('created_at', $anio)->max('id') ?? 0;

            $proveedor->codigo ??= 'PST-'.$anio.'-'.str_pad((string) ($ultimoId + 1), 4, '0', STR_PAD_LEFT);
            $proveedor->creado_por ??= auth()->id();
            $proveedor->actualizado_por ??= auth()->id();
        });

        static::updating(function (self $proveedor): void {
            $proveedor->actualizado_por = auth()->id();
        });
    }
}
