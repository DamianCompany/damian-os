<?php

namespace App\Filament\Resources\MarcasProveedorServicioTecnico\Pages;

use App\Filament\Resources\MarcasProveedorServicioTecnico\MarcaProveedorServicioTecnicoResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListarMarcasProveedorServicioTecnico extends ListRecords
{
    protected static string $resource = MarcaProveedorServicioTecnicoResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->label('Nueva marca')->icon('heroicon-o-plus')];
    }

    public function getSubheading(): ?string
    {
        return 'Marcas de máquinas, herramientas, consumibles y repuestos.';
    }
}
