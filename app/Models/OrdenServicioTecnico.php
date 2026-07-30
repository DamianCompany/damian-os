<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrdenServicioTecnico extends Model
{
    use HasFactory;

    protected $table = 'servicio_tecnico_ordenes';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'fecha_solicitada' => 'date',
            'fecha_entrega_estimada' => 'date',
            'fecha_aprobacion' => 'datetime',
            'cotizacion_generada_en' => 'datetime',
            'trabajo_iniciado_en' => 'datetime',
            'trabajo_finalizado_en' => 'datetime',
            'entregado_en' => 'datetime',
            'repuestos_antiguos_entregados' => 'boolean',
            'requiere_factura' => 'boolean',
            'costo_estimado' => 'decimal:2',
            'margen_porcentaje' => 'decimal:2',
            'precio_cotizado' => 'decimal:2',
            'base_imponible' => 'decimal:2',
            'igv_incluido' => 'decimal:2',
            'costo_real' => 'decimal:2',
            'checklist_diagnostico' => 'array',
            'repuestos' => 'array',
            'mano_obra' => 'array',
            'servicios_externos' => 'array',
            'conceptos_mantenimiento' => 'array',
            'material_usado' => 'array',
            'carpetas_drive' => 'array',
        ];
    }

    public function responsable(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsable_id');
    }

    public function archivos(): HasMany
    {
        return $this->hasMany(ArchivoServicioTecnico::class, 'orden_id');
    }

    public function historialEstados(): HasMany
    {
        return $this->hasMany(HistorialEstadoServicioTecnico::class, 'orden_id');
    }

    protected static function booted(): void
    {
        static::creating(function (self $orden): void {
            $orden->codigo ??= 'STD-OT-'.now()->format('Y').'-'.
                str_pad((string) ((self::max('id') ?? 0) + 1), 4, '0', STR_PAD_LEFT);
            $orden->responsable_id ??= auth()->id();
            $orden->creado_por ??= auth()->id();
            $orden->ubicacion_fisica ??= 'Recepción';
        });

        static::created(function (self $orden): void {
            $orden->historialEstados()->create([
                'estado_nuevo' => $orden->estado,
                'cambiado_por' => auth()->id(),
                'nota' => 'Equipo ingresado',
            ]);
        });

        static::updated(function (self $orden): void {
            if (! $orden->wasChanged('estado')) {
                return;
            }
            $orden->historialEstados()->create([
                'estado_anterior' => $orden->getPrevious()['estado'] ?? null,
                'estado_nuevo' => $orden->estado,
                'cambiado_por' => auth()->id(),
            ]);
        });
    }
}
