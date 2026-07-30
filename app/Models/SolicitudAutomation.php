<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SolicitudAutomation extends Model
{
    use HasFactory;

    protected $table = 'automation_solicitudes';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'requiere_visita' => 'boolean',
            'fecha_requerida' => 'date',
            'fecha_inicio' => 'date',
            'fecha_fin_estimada' => 'date',
            'fecha_aprobacion' => 'date',
            'fecha_instalacion' => 'date',
            'fecha_entrega' => 'date',
            'presupuesto_referencial' => 'decimal:2',
            'costo_estimado' => 'decimal:2',
            'contingencia_porcentaje' => 'decimal:2',
            'margen_porcentaje' => 'decimal:2',
            'precio_venta' => 'decimal:2',
            'costo_real' => 'decimal:2',
            'alcance_incluido' => 'array',
            'exclusiones' => 'array',
            'entregables' => 'array',
            'requerimientos_tecnicos' => 'array',
            'actividades' => 'array',
            'materiales' => 'array',
            'servicios_externos' => 'array',
            'tareas' => 'array',
            'cambios' => 'array',
            'pruebas' => 'array',
            'capacitaciones' => 'array',
            'incidencias_soporte' => 'array',
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
        return $this->hasMany(ArchivoAutomation::class, 'solicitud_id');
    }

    public function historialEstados(): HasMany
    {
        return $this->hasMany(HistorialEstadoAutomation::class, 'solicitud_id');
    }

    protected static function booted(): void
    {
        static::creating(function (self $solicitud): void {
            $solicitud->codigo ??= 'DAT-SOL-'.now()->format('Y').'-'.
                str_pad((string) ((self::max('id') ?? 0) + 1), 4, '0', STR_PAD_LEFT);
            $solicitud->responsable_id ??= auth()->id();
            $solicitud->creado_por ??= auth()->id();
        });

        static::created(function (self $solicitud): void {
            $solicitud->historialEstados()->create([
                'estado_nuevo' => $solicitud->estado,
                'cambiado_por' => auth()->id(),
                'nota' => 'Solicitud registrada',
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
