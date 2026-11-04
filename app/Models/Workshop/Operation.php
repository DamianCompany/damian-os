<?php

namespace App\Models\Workshop;

class Operation extends Record
{
    protected $table = 'workshop_operations';

    public $timestamps = false;

    protected $primaryKey = 'token';

    public $incrementing = false;

    protected $keyType = 'string';

    protected function casts(): array
    {
        return ['result' => 'array'];
    }
}
