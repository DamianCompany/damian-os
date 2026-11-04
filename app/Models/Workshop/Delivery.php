<?php

namespace App\Models\Workshop;

class Delivery extends ImmutableRecord
{
    protected $table = 'workshop_deliveries';

    protected function casts(): array
    {
        return ['cost_complete' => 'boolean', 'created_at' => 'datetime', 'cost_snapshot' => 'array'];
    }
}
