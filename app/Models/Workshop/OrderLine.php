<?php

namespace App\Models\Workshop;

class OrderLine extends Record
{
    protected static function booted(): void
    {
        static::addGlobalScope('current', fn ($q) => $q->where('workshop_order_lines.superseded', false));
    }

    protected $table = 'workshop_order_lines';

    protected function casts(): array
    {
        return ['recipe_snapshot' => 'array'];
    }
}
