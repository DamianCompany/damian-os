<?php
namespace App\Filament\Resources\ProveedoresDami3d\Widgets;
use App\Models\CategoriaProveedorDami3d; use App\Models\ProveedorDami3d; use Filament\Widgets\StatsOverviewWidget; use Filament\Widgets\StatsOverviewWidget\Stat;
class ResumenProveedoresDami3d extends StatsOverviewWidget {
 protected function getStats():array{return [
  Stat::make('Proveedores activos',ProveedorDami3d::where('estado','activo')->count())->icon('heroicon-o-check-circle')->color('success')->extraAttributes(['class'=>'damian-supplier-stat']),
  Stat::make('En evaluación',ProveedorDami3d::where('estado','evaluacion')->count())->icon('heroicon-o-clock')->color('warning')->extraAttributes(['class'=>'damian-supplier-stat']),
  Stat::make('Categorías abastecidas',CategoriaProveedorDami3d::whereHas('proveedores')->count())->icon('heroicon-o-tag')->color('info')->extraAttributes(['class'=>'damian-supplier-stat']),
  Stat::make('Suspendidos',ProveedorDami3d::where('estado','suspendido')->count())->icon('heroicon-o-pause-circle')->color('danger')->extraAttributes(['class'=>'damian-supplier-stat']),
 ];}
}
