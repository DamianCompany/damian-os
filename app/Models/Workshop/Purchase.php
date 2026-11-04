<?php

namespace App\Models\Workshop;

class Purchase extends Record
{
    protected $table = 'workshop_purchases';

    protected function casts(): array
    {
        return ['lines' => 'array', 'purchased_on' => 'date'];
    }
}
