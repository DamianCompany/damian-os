<?php

namespace App\Models;

use Database\Factories\PrinterFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Printer extends Model
{
    /** @use HasFactory<PrinterFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'location',
        'model',
        'status',
        'responsible_name',
        'next_maintenance_at',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'next_maintenance_at' => 'date',
        ];
    }
}
