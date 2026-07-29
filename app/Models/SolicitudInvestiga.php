<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SolicitudInvestiga extends Model
{
    use HasFactory;

    protected $table = 'investiga_solicitudes';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'fecha_requerida' => 'date',
            'fecha_inicio' => 'date',
            'fecha_fin_estimada' => 'date',
            'fecha_cierre' => 'date',
            'presupuesto_referencial' => 'decimal:2',
            'presupuesto_estimado' => 'decimal:2',
            'contingencia' => 'decimal:2',
            'objetivos_especificos' => 'array',
            'definicion_especializada' => 'array',
            'recursos' => 'array',
            'actividades' => 'array',
            'riesgos' => 'array',
            'datasets' => 'array',
            'experimentos' => 'array',
            'entregables' => 'array',
            'carpetas_drive' => 'array',
        ];
    }

    public function responsable(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsable_id');
    }

    public function creador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creado_por');
    }

    public function archivos(): HasMany
    {
        return $this->hasMany(ArchivoInvestiga::class, 'solicitud_id');
    }

    public function historialEstados(): HasMany
    {
        return $this->hasMany(HistorialEstadoInvestiga::class, 'solicitud_id');
    }

    protected static function booted(): void
    {
        static::creating(function (self $solicitud): void {
            $solicitud->codigo ??= 'DIL-SOL-'.now()->format('Y').'-'.
                str_pad((string) ((self::max('id') ?? 0) + 1), 4, '0', STR_PAD_LEFT);
            $solicitud->responsable_id ??= auth()->id();
            $solicitud->creado_por ??= auth()->id();
        });

        static::created(function (self $solicitud): void {
            $solicitud->historialEstados()->create([
                'estado_anterior' => null,
                'estado_nuevo' => $solicitud->estado,
                'cambiado_por' => auth()->id(),
                'nota' => 'Idea registrada',
            ]);
        });

        static::updated(function (self $solicitud): void {
            if (! $solicitud->wasChanged('estado')) {
                return;
            }

            $solicitud->historialEstados()->create([
                'estado_anterior' => $solicitud->getPrevious()['estado'] ?? null,
                'estado_nuevo' => $solicitud->estado,
                'cambiado_por' => auth()->id(),
            ]);
        });
    }
}
