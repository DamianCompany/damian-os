<?php

namespace App\Models\Workshop;

class Production extends ImmutableRecord
{
    protected $table = 'workshop_production';

    protected function casts(): array
    {
        return ['consumptions' => 'array', 'cost_snapshot' => 'array', 'final_stage' => 'boolean', 'cost_complete' => 'boolean', 'created_at' => 'datetime'];
    }
}
