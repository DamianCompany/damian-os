<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DamiOrderFile extends Model
{
    protected $fillable = [
        'type',
        'path',
        'google_drive_file_id',
        'google_drive_url',
        'google_drive_folder_id',
        'synced_to_drive_at',
    ];

    protected function casts(): array
    {
        return [
            'synced_to_drive_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(DamiOrder::class, 'dami_order_id');
    }
}
