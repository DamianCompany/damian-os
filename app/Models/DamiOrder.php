<?php

namespace App\Models;

use Database\Factories\DamiOrderFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class DamiOrder extends Model
{
    /** @use HasFactory<DamiOrderFactory> */
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'reference_images' => 'array',
            'start_date' => 'date',
            'end_date' => 'date',
            'delivery_date' => 'date',
        ];
    }

    public function printer(): BelongsTo
    {
        return $this->belongsTo(Printer::class);
    }

    protected static function booted(): void
    {
        static::creating(function (self $order): void {
            $order->order_number ??= 'D3D-'.now()->format('Ym').'-'.
                str_pad((string) ((self::max('id') ?? 0) + 1), 4, '0', STR_PAD_LEFT);
            $order->created_by ??= auth()->id();
        });

        static::saving(function (self $order): void {
            $endDate = Carbon::parse($order->end_date);
            $order->delivery_date = $endDate->addDays(3);
            $order->filament_cost = round(((float) $order->filament_grams / 1000) * 100, 2);
            $order->total_cost = round($order->filament_cost + (float) $order->electricity_cost + (float) $order->labor_cost, 2);
            $order->total_price = round((int) $order->quantity * (float) $order->unit_sale_price, 2);
            $order->profit = round($order->total_price - $order->total_cost, 2);
            $order->pending_balance = round($order->total_price - (float) $order->advance, 2);
        });
    }
}
