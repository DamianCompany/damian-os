<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class MarcaProveedorServicioTecnico extends Model
{
    protected $table = 'marcas_proveedor_servicio_tecnico';
    protected $guarded = [];

    protected function casts(): array
    {
        return ['activo' => 'boolean'];
    }

    public function proveedores(): BelongsToMany
    {
        return $this->belongsToMany(
            ProveedorServicioTecnico::class,
            'proveedor_marca_servicio_tecnico',
            'marca_id',
            'proveedor_id',
        );
    }
}
