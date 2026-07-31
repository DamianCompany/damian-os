<?php

namespace App\Filament\Resources\ProveedoresServicioTecnico\Pages;

use App\Filament\Resources\ProveedoresServicioTecnico\ProveedorServicioTecnicoResource;
use App\Filament\Resources\ProveedoresServicioTecnico\Widgets\ResumenProveedoresServicioTecnico;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListarProveedoresServicioTecnico extends ListRecords
{
    protected static string $resource = ProveedorServicioTecnicoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Registrar proveedor')->icon('heroicon-o-plus'),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [ResumenProveedoresServicioTecnico::class];
    }

    public function getSubheading(): ?string
    {
        return 'Contactos para equipos, herramientas, repuestos y consumibles.';
    }
}
