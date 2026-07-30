<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HistorialEstadoAutomation extends Model
{
    public $timestamps = false;

    protected $table = 'automation_historial_estados';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }

    public function solicitud(): BelongsTo
    {
        return $this->belongsTo(SolicitudAutomation::class, 'solicitud_id');
    }

    public function autor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cambiado_por');
    }
}
