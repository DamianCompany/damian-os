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
        'status',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }
}
