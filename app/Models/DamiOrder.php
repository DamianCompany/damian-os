<?php

namespace App\Models;

use Database\Factories\DamiOrderFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class DamiOrder extends Model
{
    /** @use HasFactory<DamiOrderFactory> */
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'delivery_date' => 'date',
            'requires_invoice' => 'boolean',
        ];
    }

    public function printer(): BelongsTo
    {
        return $this->belongsTo(Printer::class);
    }

    public function files(): HasMany
    {
        return $this->hasMany(DamiOrderFile::class);
    }

    public function referenceFiles(): HasMany
    {
        return $this->files()->where('type', 'reference');
    }

    public function statusHistory(): HasMany
    {
        return $this->hasMany(DamiOrderStatusHistory::class);
    }

    protected static function booted(): void
    {
        static::creating(function (self $order): void {
            $order->order_number ??= 'D3D-'.now()->format('Ym').'-'.
                str_pad((string) ((self::max('id') ?? 0) + 1), 4, '0', STR_PAD_LEFT);
            $order->created_by ??= auth()->id();
        });

        static::created(function (self $order): void {
            $order->statusHistory()->create([
                'from_status' => null,
                'to_status' => $order->status,
                'changed_by' => auth()->id(),
                'notes' => 'Pedido creado',
            ]);
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

        static::updated(function (self $order): void {
            if (! $order->wasChanged('status')) {
                return;
            }

            $order->statusHistory()->create([
                'from_status' => $order->getPrevious()['status'] ?? null,
                'to_status' => $order->status,
                'changed_by' => auth()->id(),
            ]);
        });
    }
}
