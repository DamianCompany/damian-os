<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DamiOrderStatusHistory extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['from_status', 'to_status', 'changed_by', 'notes'];

    public function order(): BelongsTo
    {
        return $this->belongsTo(DamiOrder::class, 'dami_order_id');
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
