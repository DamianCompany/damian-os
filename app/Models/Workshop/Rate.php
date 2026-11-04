<?php

namespace App\Models\Workshop;

class Rate extends ImmutableRecord
{
    protected $table = 'workshop_rates';

    protected function casts(): array
    {
        return ['parameters' => 'array', 'effective_on' => 'date'];
    }
}
