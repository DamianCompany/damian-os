<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ArchivoInvestiga extends Model
{
    protected $table = 'investiga_archivos';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'sincronizado_drive_en' => 'datetime',
        ];
    }

    public function solicitud(): BelongsTo
    {
        return $this->belongsTo(SolicitudInvestiga::class, 'solicitud_id');
    }
}
