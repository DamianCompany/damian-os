<?php

namespace App\Models\Workshop;

class Item extends Record
{
    protected $table = 'workshop_items';

    protected function casts(): array
    {
        return ['recipe' => 'array', 'favorite' => 'boolean', 'active' => 'boolean'];
    }
}
