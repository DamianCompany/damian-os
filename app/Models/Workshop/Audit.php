<?php

namespace App\Models\Workshop;

class Audit extends ImmutableRecord
{
    protected $table = 'workshop_audit';

    protected function casts(): array
    {
        return ['changes' => 'array', 'created_at' => 'datetime'];
    }
}
