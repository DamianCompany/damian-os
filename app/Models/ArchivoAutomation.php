<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ArchivoAutomation extends Model
{
    protected $table = 'automation_archivos';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['sincronizado_drive_en' => 'datetime'];
    }

    public function solicitud(): BelongsTo
    {
        return $this->belongsTo(SolicitudAutomation::class, 'solicitud_id');
    }
}
