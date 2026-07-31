<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class CategoriaProveedorServicioTecnico extends Model
{
    protected $table = 'categorias_proveedor_servicio_tecnico';
    protected $guarded = [];

    protected function casts(): array
    {
        return ['activo' => 'boolean'];
    }

    public function proveedores(): BelongsToMany
    {
        return $this->belongsToMany(
            ProveedorServicioTecnico::class,
            'proveedor_categoria_servicio_tecnico',
            'categoria_id',
            'proveedor_id',
        );
    }
}
