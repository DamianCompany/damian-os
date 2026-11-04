<?php

namespace App\Models\Workshop;

class Task extends Record
{
    protected $table = 'workshop_tasks';

    protected function casts(): array
    {
        return ['planned_start' => 'datetime', 'planned_end' => 'datetime', 'started_at' => 'datetime', 'manual_started_at' => 'datetime', 'final_stage' => 'boolean'];
    }
}
