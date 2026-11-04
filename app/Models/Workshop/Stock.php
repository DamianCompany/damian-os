<?php

namespace App\Models\Workshop;

class Stock extends ImmutableRecord
{
    protected $table = 'workshop_stock';

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }
}
