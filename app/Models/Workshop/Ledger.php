<?php

namespace App\Models\Workshop;

class Ledger extends ImmutableRecord
{
    protected $table = 'workshop_ledger';

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }
}
