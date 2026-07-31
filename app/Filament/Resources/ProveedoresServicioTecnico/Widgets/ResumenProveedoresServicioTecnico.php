<?php

namespace App\Filament\Resources\ProveedoresServicioTecnico\Widgets;

use App\Models\CategoriaProveedorServicioTecnico;
use App\Models\ProveedorServicioTecnico;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ResumenProveedoresServicioTecnico extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Proveedores activos', ProveedorServicioTecnico::where('estado', 'activo')->count())
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->extraAttributes(['class' => 'damian-supplier-stat damian-technical-supplier-stat']),
            Stat::make('En evaluación', ProveedorServicioTecnico::where('estado', 'evaluacion')->count())
                ->icon('heroicon-o-clock')
                ->color('warning')
                ->extraAttributes(['class' => 'damian-supplier-stat damian-technical-supplier-stat']),
            Stat::make('Categorías cubiertas', CategoriaProveedorServicioTecnico::whereHas('proveedores')->count())
                ->icon('heroicon-o-tag')
                ->color('info')
                ->extraAttributes(['class' => 'damian-supplier-stat damian-technical-supplier-stat']),
        ];
    }
}
