<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HistorialEstadoServicioTecnico extends Model
{
    public $timestamps = false;
    protected $table = 'servicio_tecnico_historial_estados';
    protected $guarded = [];

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }

    public function orden(): BelongsTo
    {
        return $this->belongsTo(OrdenServicioTecnico::class, 'orden_id');
    }

    public function autor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cambiado_por');
    }
}
