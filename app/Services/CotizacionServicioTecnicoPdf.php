<?php

namespace App\Services;

use App\Models\OrdenServicioTecnico;
use Barryvdh\DomPDF\Facade\Pdf;
use NumberFormatter;

class CotizacionServicioTecnicoPdf
{
    public function generar(OrdenServicioTecnico $orden): string
    {
        return Pdf::loadView('pdf.cotizacion-servicio-tecnico', [
            'orden' => $orden,
            'items' => $this->items($orden),
            'importeLetras' => $this->importeEnLetras((float) $orden->precio_cotizado),
        ])->setPaper([0, 0, 226.77, 720])->output();
    }

    public function nombreArchivo(OrdenServicioTecnico $orden): string
    {
        return "COTIZACION-{$orden->codigo}.pdf";
    }

    private function items(OrdenServicioTecnico $orden): array
    {
        if ($orden->tipo_atencion === 'mantenimiento') {
            return collect($orden->conceptos_mantenimiento ?? [])->map(fn (array $item): array => [
                'descripcion' => $item['descripcion'] ?? 'Mantenimiento',
                'cantidad' => (float) ($item['cantidad'] ?? 1),
                'precio' => (float) ($item['precio_unitario'] ?? 0),
            ])->all();
        }

        $repuestos = collect($orden->repuestos ?? [])->map(fn (array $item): array => [
            'descripcion' => $item['descripcion'] ?? 'Repuesto',
            'cantidad' => (float) ($item['cantidad'] ?? 1),
            'precio' => (float) ($item['costo'] ?? 0),
        ]);
        $manoObra = collect($orden->mano_obra ?? [])->map(fn (array $item): array => [
            'descripcion' => 'Mano de obra: '.($item['actividad'] ?? 'Servicio técnico'),
            'cantidad' => 1,
            'precio' => isset($item['monto'])
                ? (float) $item['monto']
                : (float) ($item['horas'] ?? 0) * (float) ($item['tarifa'] ?? 0),
        ]);
        $externos = collect($orden->servicios_externos ?? [])->map(fn (array $item): array => [
            'descripcion' => $item['descripcion'] ?? 'Servicio externo',
            'cantidad' => 1,
            'precio' => (float) ($item['costo'] ?? 0),
        ]);

        return $repuestos->concat($manoObra)->concat($externos)->values()->all();
    }

    private function importeEnLetras(float $total): string
    {
        $soles = (int) floor($total);
        $centimos = (int) round(($total - $soles) * 100);
        $texto = class_exists(NumberFormatter::class)
            ? (new NumberFormatter('es_PE', NumberFormatter::SPELLOUT))->format($soles)
            : (string) $soles;

        return mb_strtoupper("{$texto} Y ".str_pad((string) $centimos, 2, '0', STR_PAD_LEFT).'/100 SOLES');
    }
}
