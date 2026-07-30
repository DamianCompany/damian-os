<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ArchivoServicioTecnico extends Model
{
    protected $table = 'servicio_tecnico_archivos';
    protected $guarded = [];

    protected function casts(): array
    {
        return ['sincronizado_drive_en' => 'datetime'];
    }

    public function orden(): BelongsTo
    {
        return $this->belongsTo(OrdenServicioTecnico::class, 'orden_id');
    }
}
