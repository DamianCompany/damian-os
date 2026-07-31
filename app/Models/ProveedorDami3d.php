<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProveedorDami3d extends Model
{
    use SoftDeletes;

    protected $table = 'proveedores_dami3d';
    protected $guarded = [];

    protected function casts(): array
    {
        return ['principal' => 'boolean', 'emite_factura' => 'boolean', 'emite_boleta' => 'boolean', 'ofrece_garantia' => 'boolean', 'calificacion' => 'decimal:2'];
    }

    public function categorias(): BelongsToMany
    {
        return $this->belongsToMany(CategoriaProveedorDami3d::class, 'proveedor_categoria_dami3d', 'proveedor_id', 'categoria_id');
    }

    public function marcas(): BelongsToMany
    {
        return $this->belongsToMany(MarcaProveedorDami3d::class, 'proveedor_marca_dami3d', 'proveedor_id', 'marca_id')->withPivot('principal');
    }

    public function productos(): HasMany { return $this->hasMany(ProductoProveedorDami3d::class, 'proveedor_id'); }
    public function evaluaciones(): HasMany { return $this->hasMany(EvaluacionProveedorDami3d::class, 'proveedor_id'); }
    public function incidencias(): HasMany { return $this->hasMany(IncidenciaProveedorDami3d::class, 'proveedor_id'); }
    public function documentos(): HasMany { return $this->hasMany(DocumentoProveedorDami3d::class, 'proveedor_id'); }
    public function actividad(): HasMany { return $this->hasMany(ActividadProveedorDami3d::class, 'proveedor_id'); }

    protected static function booted(): void
    {
        static::creating(function (self $proveedor): void {
            $year = now()->year;
            $ultimo = self::withTrashed()->whereYear('created_at', $year)->max('id') ?? 0;
            $proveedor->codigo ??= 'PROV-'.$year.'-'.str_pad((string) ($ultimo + 1), 4, '0', STR_PAD_LEFT);
            $proveedor->creado_por ??= auth()->id();
            $proveedor->actualizado_por ??= auth()->id();
        });
        static::created(fn (self $p) => $p->actividad()->create(['accion' => 'Creación', 'detalle' => 'Proveedor registrado en evaluación.', 'usuario_id' => auth()->id()]));
        static::updating(fn (self $p) => $p->actualizado_por = auth()->id());
        static::updated(function (self $p): void {
            $cambios = collect($p->getChanges())->except(['updated_at', 'actualizado_por'])->keys()->implode(', ');
            if ($cambios) $p->actividad()->create(['accion' => 'Actualización', 'detalle' => "Campos modificados: {$cambios}", 'usuario_id' => auth()->id()]);
        });
    }
}
